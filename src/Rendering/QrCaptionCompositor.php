<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Adds a caption strip under rendered PNG bytes, widening the canvas when the
 * caption is wider than the QR. Imagick when available, GD otherwise.
 *
 * @internal Not an extension point; may change without notice. Use QrCodeService.
 */
final class QrCaptionCompositor
{
    public function apply(string $qrPngData, QrRenderOptions $options): string
    {
        $text = $options->overlayText;
        if ($text === null || $text === '') {
            return $qrPngData;
        }

        return $this->applyText($qrPngData, $text, $options->fontSize, $options->fontColor, $options->fontPath, $options->backgroundColor);
    }

    /**
     * Shared with BarcodeRenderer, which draws its human-readable digits
     * through the same caption strip.
     */
    public function applyText(string $pngData, string $text, int $fontSize, string $fontColor, ?string $fontPath, string $backgroundColor): string
    {
        if ($text === '') {
            return $pngData;
        }

        if (extension_loaded('imagick')) {
            try {
                return $this->withImagick($pngData, $text, $fontSize, $fontColor, $fontPath, $backgroundColor);
            } catch (\Throwable) {
                // If Imagick fails (e.g. FreeType font missing or not configured in container), gracefully fall back to GD
            }
        }

        return $this->withGd($pngData, $text, $fontSize, $fontColor, $backgroundColor);
    }

    private function withImagick(string $qrPngData, string $text, int $fontSize, string $fontColor, ?string $fontPath, string $backgroundColor): string
    {
        $imagick = new Imagick;
        $imagick->readImageBlob($qrPngData);

        $qrWidth = $imagick->getImageWidth();
        $qrHeight = $imagick->getImageHeight();

        $draw = new ImagickDraw;
        $draw->setFontSize($fontSize);
        $draw->setFillColor(new ImagickPixel($fontColor));
        $draw->setTextAlignment(Imagick::ALIGN_CENTER);

        if ($fontPath !== null && file_exists($fontPath)) {
            $draw->setFont($fontPath);
        }

        $metrics = $imagick->queryFontMetrics($draw, $text);
        $textHeight = (int) $metrics['textHeight'];

        $padding = 12;
        $newHeight = $qrHeight + $textHeight + $padding;
        $newWidth = max($qrWidth, (int) ceil($metrics['textWidth']) + 2 * $padding);

        $canvas = new Imagick;
        $canvas->newImage($newWidth, $newHeight, new ImagickPixel($backgroundColor));
        $canvas->setImageFormat('png');

        $canvas->compositeImage($imagick, Imagick::COMPOSITE_OVER, intdiv($newWidth - $qrWidth, 2), 0);

        $textY = $qrHeight + (int) ($textHeight * 0.8);
        $canvas->annotateImage($draw, $newWidth / 2, (float) $textY, 0.0, $text);

        $imagick->destroy();
        $result = $canvas->getImageBlob();
        $canvas->destroy();

        return $result;
    }

    private function withGd(string $qrPngData, string $text, int $fontSize, string $fontColor, string $backgroundColor): string
    {
        $qrImage = imagecreatefromstring($qrPngData);
        if ($qrImage === false) {
            return $qrPngData;
        }

        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        $fontId = min(5, max(1, (int) floor($fontSize / 4)));
        $textWidth = strlen($text) * imagefontwidth($fontId);
        $textHeight = imagefontheight($fontId);
        $padding = 10;

        $newHeight = max(1, $qrHeight + $textHeight + $padding);
        $newWidth = max(1, max($qrWidth, $textWidth + 2 * $padding));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        if ($canvas === false) {
            return $qrPngData;
        }

        $bgRgb = HexColor::toRgb($backgroundColor);
        $bg = imagecolorallocate($canvas, $bgRgb['r'], $bgRgb['g'], $bgRgb['b']);
        if ($bg !== false) {
            imagefill($canvas, 0, 0, $bg);
        }

        imagecopy($canvas, $qrImage, intdiv($newWidth - $qrWidth, 2), 0, 0, 0, $qrWidth, $qrHeight);

        $fgRgb = HexColor::toRgb($fontColor);
        $textColor = imagecolorallocate($canvas, $fgRgb['r'], $fgRgb['g'], $fgRgb['b']);

        if ($textColor !== false) {
            imagestring($canvas, $fontId, intdiv($newWidth - $textWidth, 2), $qrHeight + 2, $text, $textColor);
        }

        ob_start();
        imagepng($canvas);

        return (string) ob_get_clean();
    }
}
