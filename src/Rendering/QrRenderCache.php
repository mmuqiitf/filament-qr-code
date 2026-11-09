<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Two-level render cache: L1 in-process (process-wide, 200-entry FIFO) and an
 * optional best-effort L2 on the Laravel cache. State is static so every
 * QrCodeService instance in a process shares it. Octane clears L1 when a
 * request, task, or tick starts. Other long-running workers keep L1 until
 * flushRenderCache() or flushPersistentCache().
 *
 * L2 keys carry a generation, filament-qr-code:render:vN:. flushPersistent()
 * bumps N and clears L1. It does not list or delete entries, and it does not
 * touch other keys in the store. Retired entries expire with the cache TTL.
 *
 * @internal Not an extension point; may change without notice. Use QrCodeService.
 */
final class QrRenderCache
{
    private const L1_LIMIT = 200;

    private const KEY_PREFIX = 'filament-qr-code:render:';

    private const VERSION_KEY = 'filament-qr-code:render-version';

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

        $persistentKey = self::persistentKey($key);
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
            self::bumpVersion(self::store());
        } catch (Throwable) {
            // Cache backend unavailable — L1 is already clear.
        }
    }

    private static function persistentKey(string $key): string
    {
        return self::KEY_PREFIX.'v'.self::version().':'.$key;
    }

    private static function version(): int
    {
        if (! self::persistentActive()) {
            return 1;
        }

        try {
            $version = self::store()->get(self::VERSION_KEY);
        } catch (Throwable) {
            return 1;
        }

        return is_numeric($version) && (int) $version > 0 ? (int) $version : 1;
    }

    /**
     * A missing version is generation 1. File and array stores treat
     * increment of a missing key as 1, which would not retire that generation.
     */
    private static function bumpVersion(Repository $store): void
    {
        $current = $store->get(self::VERSION_KEY);

        if (! is_numeric($current) || (int) $current < 1) {
            $store->forever(self::VERSION_KEY, 2);

            return;
        }

        $next = $store->increment(self::VERSION_KEY);

        if ($next === false) {
            $store->forever(self::VERSION_KEY, ((int) $current) + 1);
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
            } elseif ($ttl > 0) {
                $store->put($key, $value, $ttl);
            }
        } catch (Throwable) {
            // Persistent caching is best-effort; rendering already succeeded.
        }
    }

    private static function store(): Repository
    {
        return Cache::store(self::$store);
    }
}
