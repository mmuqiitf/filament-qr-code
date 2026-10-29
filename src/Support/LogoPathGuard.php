<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

/**
 * Restricts logo files to the directories listed in
 * `qr-code.generator.logo_base_paths`. With no base paths configured every
 * path is allowed (1.0.0 behaviour).
 *
 * @internal Not covered by semver; may change in any release.
 */
class LogoPathGuard
{
    public static function configured(): bool
    {
        return self::basePaths() !== null;
    }

    public static function allows(string $path): bool
    {
        $basePaths = self::basePaths();

        if ($basePaths === null) {
            return true;
        }

        if ($path === '') {
            return false;
        }

        $real = realpath($path);

        if ($real === false || ! is_file($real) || ! self::isImage($real)) {
            return false;
        }

        foreach ($basePaths as $basePath) {
            $base = realpath($basePath);

            if ($base !== false && is_dir($base) && str_starts_with($real, rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string>|null */
    public static function basePaths(): ?array
    {
        $value = function_exists('config') ? config('qr-code.generator.logo_base_paths') : null;

        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, fn ($path) => is_string($path) && $path !== ''));
    }

    private static function isImage(string $path): bool
    {
        $info = @getimagesize($path);

        return $info !== false && in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true);
    }
}
