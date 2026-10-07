<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use RuntimeException;
use ZipArchive;

class QrZipArchive
{
    /**
     * Pack pre-rendered QR binaries into a ZIP archive.
     *
     * @param  array<string, string>  $files  filename => raw image bytes
     */
    public static function fromFiles(array $files): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Bulk QR export needs the PHP zip extension.');
        }

        $path = tempnam(sys_get_temp_dir(), 'filament-qr-');
        if ($path === false) {
            throw new RuntimeException('Could not create a temporary file for the QR archive.');
        }

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the QR archive for writing.');
        }

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        $archive = file_get_contents($path);
        @unlink($path);

        if (! is_string($archive)) {
            throw new RuntimeException('Could not read the finished QR archive.');
        }

        return $archive;
    }
}
