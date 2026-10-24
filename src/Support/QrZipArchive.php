<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Mmuqiitf\FilamentQrCode\Rendering\QrZipArchiver;

/**
 * Kept for backwards compatibility; delegates to QrZipArchiver.
 *
 * @internal Not covered by semver; may change in any release.
 */
class QrZipArchive
{
    /**
     * Pack pre-rendered QR binaries into a ZIP archive.
     *
     * @param  array<string, string>  $files  filename => raw image bytes
     */
    public static function fromFiles(array $files): string
    {
        return (new QrZipArchiver)->pack($files);
    }
}
