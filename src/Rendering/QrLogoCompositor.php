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

        $qrImage = imagecreatefromstring($qrPngData);
        if ($qrImage === false) {
            return $qrPngData;
        }

        $logoData = file_get_contents($logoPath);
        if ($logoData === false) {
            return $qrPngData;
        }

        $logoImage = imagecreatefromstring($logoData);
        if ($logoImage === false) {
            return $qrPngData;
        }

        $dstX = (int) ((imagesx($qrImage) - $logoSize) / 2);
        $dstY = (int) ((imagesy($qrImage) - $logoSize) / 2);

        imagecopyresampled(
            $qrImage,
            $logoImage,
            $dstX,
            $dstY,
            0,
            0,
            $logoSize,
            $logoSize,
            imagesx($logoImage),
            imagesy($logoImage),
        );

        ob_start();
        imagepng($qrImage);

        return (string) ob_get_clean();
    }
}
