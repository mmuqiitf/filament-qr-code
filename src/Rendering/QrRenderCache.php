<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Two-level render cache: L1 in-process (process-wide, 200-entry FIFO) and an
 * optional best-effort L2 on the Laravel cache. State is static so every
 * QrCodeService instance in a process shares it. Long-running workers keep
 * L1 until flushRenderCache() or flushPersistentCache().
 *
 * L2 entries are tracked in an index. flushPersistent() deletes that index
 * and those entries. It does not flush the rest of the cache store.
 *
 * @internal Not an extension point; may change without notice. Use QrCodeService.
 */
final class QrRenderCache
{
    private const L1_LIMIT = 200;

    private const KEY_PREFIX = 'filament-qr-code:render:';

    private const INDEX_KEY = 'filament-qr-code:render-index';

    /** @var array<string, string> */
    private static array $memory = [];

    private static bool $persistentEnabled = true;

    private static int|false|null $ttl = null;

    private static ?string $store = null;

    /**
     * @param  callable(): string  $render
     */
    public static function remember(string $key, callable $render): string
    {
        if (isset(self::$memory[$key])) {
            return self::$memory[$key];
        }

        $persistentKey = self::KEY_PREFIX.$key;
        $hit = self::readPersistent($persistentKey);
        if ($hit !== null) {
            self::$memory[$key] = $hit;

            return $hit;
        }

        $value = $render();

        if (count(self::$memory) >= self::L1_LIMIT) {
            array_shift(self::$memory);
        }

        self::$memory[$key] = $value;
        self::writePersistent($persistentKey, $value);

        return $value;
    }

    public static function enablePersistent(bool $enabled = true): void
    {
        self::$persistentEnabled = $enabled;
    }

    public static function setTtl(int|false|null $seconds): void
    {
        self::$ttl = $seconds;
    }

    public static function setStore(?string $store): void
    {
        self::$store = $store;
    }

    /**
     * Seconds to keep L2 entries; null = forever; false = L2 off.
     */
    public static function effectiveTtl(): int|false|null
    {
        if (self::$ttl !== null) {
            return self::$ttl;
        }

        if (function_exists('config')) {
            $configured = config('qr-code.generator.cache_ttl');
            if ($configured === null || $configured === false) {
                return $configured;
            }

            return is_numeric($configured) ? max(0, (int) $configured) : 86400;
        }

        return 86400;
    }

    public static function flushMemory(): void
    {
        self::$memory = [];
    }

    public static function flushPersistent(): void
    {
        self::flushMemory();

        try {
            $store = self::store();
            $index = $store->get(self::INDEX_KEY, []);

            if (is_array($index)) {
                foreach ($index as $key => $expiresAt) {
                    if (is_string($key)) {
                        $store->forget($key);
                    }
                }
            }

            $store->forget(self::INDEX_KEY);
        } catch (Throwable) {
            // Cache backend unavailable — L1 is already clear.
        }
    }

    private static function persistentActive(): bool
    {
        return self::$persistentEnabled && self::effectiveTtl() !== false;
    }

    private static function readPersistent(string $key): ?string
    {
        if (! self::persistentActive()) {
            return null;
        }

        try {
            $value = Cache::store(self::$store)->get($key);

            return is_string($value) && $value !== '' ? $value : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function writePersistent(string $key, string $value): void
    {
        if (! self::persistentActive()) {
            return;
        }

        try {
            $store = self::store();
            $ttl = self::effectiveTtl();

            if ($ttl === null) {
                $store->forever($key, $value);
                self::rememberIndex($store, $key, null);
            } elseif ($ttl > 0) {
                $store->put($key, $value, $ttl);
                self::rememberIndex($store, $key, $ttl);
            }
        } catch (Throwable) {
            // Persistent caching is best-effort; rendering already succeeded.
        }
    }

    private static function store(): Repository
    {
        return Cache::store(self::$store);
    }

    private static function rememberIndex(Repository $store, string $key, ?int $ttl): void
    {
        $write = function () use ($store, $key, $ttl): void {
            $now = time();
            $index = $store->get(self::INDEX_KEY, []);

            if (! is_array($index)) {
                $index = [];
            }

            foreach ($index as $indexedKey => $expiresAt) {
                if (! is_string($indexedKey) || (is_int($expiresAt) && $expiresAt <= $now)) {
                    unset($index[$indexedKey]);
                }
            }

            $index[$key] = $ttl === null ? null : $now + $ttl;
            $store->forever(self::INDEX_KEY, $index);
        };

        try {
            $write();
        } catch (Throwable) {
            // The render is stored. A missed index entry only means a later
            // flush will not find this one key. Two workers updating the
            // index at once can drop a key the same way.
        }
    }
}
