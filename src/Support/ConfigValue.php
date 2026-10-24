<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

/**
 * @internal Not covered by semver; may change in any release.
 */
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

    /**
     * @param  list<int>  $default
     * @return list<int>
     */
    public static function intList(string $key, array $default): array
    {
        $value = self::read($key, $default);

        if (! is_array($value) || ! array_is_list($value)) {
            return $default;
        }

        foreach ($value as $item) {
            if (! is_int($item) || $item < 0) {
                return $default;
            }
        }

        return $value;
    }

    private static function read(string $key, mixed $default): mixed
    {
        if (! function_exists('config')) {
            return $default;
        }

        return config($key, $default);
    }
}
