<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Illuminate\Support\Facades\Cache;

/**
 * Two-level render cache: L1 in-process (per request, 200-entry FIFO) and an
 * optional best-effort L2 on the Laravel cache. State is static so every
 * QrCodeService instance in a request shares it.
 *
 * @internal Not an extension point; may change without notice. Use QrCodeService.
 */
final class QrRenderCache
{
    private const L1_LIMIT = 200;

    private const KEY_PREFIX = 'filament-qr-code:render:';

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
        if (! self::$persistentEnabled) {
            return;
        }

        try {
            Cache::store(self::$store)->flush();
        } catch (\Throwable) {
            // Cache backend unavailable (e.g. array driver in tests) — L1 still works.
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
        } catch (\Throwable) {
            return null;
        }
    }

    private static function writePersistent(string $key, string $value): void
    {
        if (! self::persistentActive()) {
            return;
        }

        try {
            $store = Cache::store(self::$store);

            $ttl = self::effectiveTtl();
            if ($ttl === null) {
                $store->forever($key, $value);
            } elseif ($ttl > 0) {
                $store->put($key, $value, $ttl);
            }
        } catch (\Throwable) {
            // Persistent caching is best-effort; rendering already succeeded.
        }
    }
}
