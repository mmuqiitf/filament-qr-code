<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use JsonException;

/**
 * Identity of one render. The payload is JSON, so a "|" inside a caption
 * or a path cannot slide into the next field. Logo and font files contribute
 * mtime and size, so replacing a file in place misses the cache.
 *
 * @internal Not an extension point; may change without notice.
 */
final class RenderCacheKey
{
    /**
     * @param  array<string, mixed>  $parts
     */
    public static function make(string $kind, array $parts): string
    {
        try {
            $payload = json_encode($parts, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            $payload = serialize($parts);
        }

        return $kind.':'.hash('sha256', $payload);
    }

    /**
     * @return array{path: string, mtime: int|null, size: int|null}|null
     */
    public static function fileToken(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        clearstatcache(true, $path);
        $mtime = @filemtime($path);
        $size = @filesize($path);

        return [
            'path' => $path,
            'mtime' => $mtime === false ? null : $mtime,
            'size' => $size === false ? null : $size,
        ];
    }
}
