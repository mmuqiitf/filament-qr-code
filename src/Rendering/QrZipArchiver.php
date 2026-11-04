<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * @internal Not an extension point; may change without notice. Use QrCodeService / DownloadQrBulkAction.
 */
final class QrZipArchiver
{
    /**
     * Pack pre-rendered QR binaries into a ZIP archive.
     *
     * @param  array<string, string>  $files  filename => raw image bytes
     */
    public function pack(array $files): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Bulk QR export needs the PHP zip extension.');
        }

        $path = tempnam(sys_get_temp_dir(), 'filament-qr-');
        if ($path === false) {
            throw new RuntimeException('Could not create a temporary file for the QR archive.');
        }

        $zip = new ZipArchive;
        $opened = false;
        $closed = false;

        try {
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not open the QR archive for writing.');
            }

            $opened = true;

            foreach (self::entries($files) as $name => $contents) {
                if ($zip->addFromString($name, $contents) !== true) {
                    throw new RuntimeException('Could not add a QR image to the archive.');
                }
            }

            if ($zip->close() !== true) {
                throw new RuntimeException('Could not finish the QR archive.');
            }

            $closed = true;
            $archive = file_get_contents($path);

            if (! is_string($archive)) {
                throw new RuntimeException('Could not read the finished QR archive.');
            }

            return $archive;
        } catch (Throwable $exception) {
            if ($opened && ! $closed) {
                $zip->close();
            }

            throw $exception instanceof RuntimeException
                ? $exception
                : new RuntimeException('Could not build the QR archive.', 0, $exception);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    /**
     * Basename only, so a file name taken from a record cannot store
     * `../` inside the archive. An empty result becomes `qrcode`.
     */
    public static function safeEntryName(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $base = preg_replace('/[\x00-\x1F\x7F]/u', '', $base) ?? '';

        if ($base === '' || $base === '.' || $base === '..') {
            return 'qrcode';
        }

        return $base;
    }

    /**
     * @param  array<string, string>  $used  entry name => contents already chosen
     */
    public static function uniqueEntryName(string $name, array $used): string
    {
        $safe = self::safeEntryName($name);

        if (! array_key_exists($safe, $used)) {
            return $safe;
        }

        $dot = strrpos($safe, '.');
        $stem = $dot === false ? $safe : substr($safe, 0, $dot);
        $extension = $dot === false ? '' : substr($safe, $dot);
        $suffix = 2;

        do {
            $candidate = $stem.'-'.$suffix.$extension;
            $suffix++;
        } while (array_key_exists($candidate, $used));

        return $candidate;
    }

    /**
     * @param  array<string, string>  $files
     * @return array<string, string>
     */
    private static function entries(array $files): array
    {
        $named = [];

        foreach ($files as $name => $contents) {
            $named[self::uniqueEntryName((string) $name, $named)] = $contents;
        }

        return $named;
    }
}
