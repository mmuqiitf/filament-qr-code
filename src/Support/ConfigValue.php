<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

class ConfigValue
{
    public static function bool(string $key, bool $default): bool
    {
        return (bool) self::read($key, $default);
    }

    public static function int(string $key, int $default, int $minimum = PHP_INT_MIN): int
    {
        $value = self::read($key, $default);

        if (! is_numeric($value)) {
            return max($minimum, $default);
        }

        return max($minimum, (int) $value);
    }

    private static function read(string $key, mixed $default): mixed
    {
        if (! function_exists('config')) {
            return $default;
        }

        return config($key, $default);
    }
}
