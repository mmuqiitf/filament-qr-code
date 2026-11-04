<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Imagick;

/**
 * Centers a logo over rendered PNG bytes. Imagick when available, GD otherwise;
 * any failure returns the QR untouched.
 *
 * @internal Not an extension point; may change without notice. Use QrCodeService.
 */
final class QrLogoCompositor
{
    public function apply(string $qrPngData, string $logoPath, int $logoSize): string
    {
        if (extension_loaded('imagick')) {
            try {
                $qr = new Imagick;
                $qr->readImageBlob($qrPngData);

                $logo = new Imagick($logoPath);
                $logo->thumbnailImage($logoSize, $logoSize, true);

                $x = (int) (($qr->getImageWidth() - $logo->getImageWidth()) / 2);
                $y = (int) (($qr->getImageHeight() - $logo->getImageHeight()) / 2);

                $qr->compositeImage($logo, Imagick::COMPOSITE_OVER, $x, $y);
                $result = $qr->getImageBlob();

                $qr->destroy();
                $logo->destroy();

                return $result;
            } catch (\Throwable) {
                // Fall back to GD
            }
        }

        return $this->applyGd($qrPngData, $logoPath, $logoSize);
    }

    /**
     * GD logo pass. Public so tests can exercise it where Imagick is loaded.
     *
     * @internal Not an extension point.
     */
    public function applyGd(string $qrPngData, string $logoPath, int $logoSize): string
    {
        $qrImage = imagecreatefromstring($qrPngData);
        if ($qrImage === false) {
            return $qrPngData;
        }

        /** @var \GdImage|false $logoImage */
        $logoImage = false;

        try {
            $logoData = file_get_contents($logoPath);
            if ($logoData === false) {
                return $qrPngData;
            }

            $logoImage = imagecreatefromstring($logoData);
            if ($logoImage === false) {
                return $qrPngData;
            }

            [$dstWidth, $dstHeight] = self::fittedSize(imagesx($logoImage), imagesy($logoImage), $logoSize);
            $dstX = (int) ((imagesx($qrImage) - $dstWidth) / 2);
            $dstY = (int) ((imagesy($qrImage) - $dstHeight) / 2);

            imagecopyresampled(
                $qrImage,
                $logoImage,
                $dstX,
                $dstY,
                0,
                0,
                $dstWidth,
                $dstHeight,
                imagesx($logoImage),
                imagesy($logoImage),
            );

            ob_start();
            imagepng($qrImage);

            return (string) ob_get_clean();
        } finally {
            // Drop the GdImage handles before returning. imagedestroy() is
            // deprecated on PHP 8.5; destroying the object frees the buffer.
            $logoImage = null;
            $qrImage = null;
        }
    }

    /**
     * Largest size that fits inside a $box × $box square without stretching.
     *
     * @return array{0: int, 1: int}
     *
     * @internal Not an extension point.
     */
    public static function fittedSize(int $sourceWidth, int $sourceHeight, int $box): array
    {
        if ($sourceWidth < 1 || $sourceHeight < 1 || $box < 1) {
            return [max(1, $box), max(1, $box)];
        }

        $scale = min($box / $sourceWidth, $box / $sourceHeight);

        return [
            max(1, (int) round($sourceWidth * $scale)),
            max(1, (int) round($sourceHeight * $scale)),
        ];
    }
}
