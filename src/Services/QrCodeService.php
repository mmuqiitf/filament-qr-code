<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Response;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrCodeService
{
    protected ?string $data = null;

    protected string $rawResult = '';

    protected int $size = 300;

    protected int $margin = 2;

    protected QrFormat $format = QrFormat::Svg;

    protected string $foregroundColor = '#000000';

    protected string $backgroundColor = '#ffffff';

    protected ?ErrorCorrectionLevel $errorCorrectionLevel = null;

    protected ?string $overlayText = null;

    protected int $fontSize = 16;

    protected string $fontColor = '#000000';

    protected ?string $logoPath = null;

    protected int $logoSize = 50;

    protected string $fileName = 'qrcode';

    public function __construct()
    {
        $this->errorCorrectionLevel = ErrorCorrectionLevel::M();
    }

    public static function make(): self
    {
        return new self();
    }

    public function size(int $size): self
    {
        $this->size = max(50, $size);

        return $this;
    }

    public function margin(int $margin): self
    {
        $this->margin = max(0, $margin);

        return $this;
    }

    public function format(QrFormat|string $format): self
    {
        if (is_string($format)) {
            $format = QrFormat::tryFrom(strtolower($format)) ?? QrFormat::Svg;
        }

        $this->format = $format;

        return $this;
    }

    public function color(string $hexColor): self
    {
        $this->foregroundColor = $hexColor;

        return $this;
    }

    public function backgroundColor(string $hexColor): self
    {
        $this->backgroundColor = $hexColor;

        return $this;
    }

    public function errorCorrection(string|ErrorCorrectionLevel $level): self
    {
        if (is_string($level)) {
            $this->errorCorrectionLevel = match (strtoupper($level)) {
                'L' => ErrorCorrectionLevel::L(),
                'Q' => ErrorCorrectionLevel::Q(),
                'H' => ErrorCorrectionLevel::H(),
                default => ErrorCorrectionLevel::M(),
            };
        } else {
            $this->errorCorrectionLevel = $level;
        }

        return $this;
    }

    public function withText(string $text, int $fontSize = 16, string $fontColor = '#000000'): self
    {
        $this->overlayText = $text;
        $this->fontSize = $fontSize;
        $this->fontColor = $fontColor;

        // Overlay text requires PNG format for bitmap rasterization
        $this->format = QrFormat::Png;

        return $this;
    }

    public function logo(string $path, int $size = 50): self
    {
        $this->logoPath = $path;
        $this->logoSize = $size;
        $this->errorCorrectionLevel = ErrorCorrectionLevel::H();
        $this->format = QrFormat::Png;

        return $this;
    }

    public function fileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function generate(string $data): self
    {
        $this->data = $data;
        $ecLevel = $this->errorCorrectionLevel ?? ErrorCorrectionLevel::M();

        $fgRgb = $this->hexToRgb($this->foregroundColor);
        $bgRgb = $this->hexToRgb($this->backgroundColor);

        $fill = Fill::uniformColor(
            new Rgb($bgRgb['r'], $bgRgb['g'], $bgRgb['b']),
            new Rgb($fgRgb['r'], $fgRgb['g'], $fgRgb['b'])
        );

        $rendererStyle = new RendererStyle(
            $this->size,
            $this->margin,
            null,
            null,
            $fill
        );

        if ($this->format === QrFormat::Svg) {
            $renderer = new ImageRenderer($rendererStyle, new SvgImageBackEnd());
            $writer = new Writer($renderer);
            $this->rawResult = $writer->writeString($data, 'UTF-8', $ecLevel);
        } else {
            // PNG rendering
            if (extension_loaded('imagick')) {
                $renderer = new ImageRenderer($rendererStyle, new ImagickImageBackEnd());
                $writer = new Writer($renderer);
                $pngData = $writer->writeString($data, 'UTF-8', $ecLevel);
            } else {
                $renderer = new GDLibRenderer($this->size, $this->margin);
                $writer = new Writer($renderer);
                $pngData = $writer->writeString($data, 'UTF-8', $ecLevel);
            }

            if ($this->logoPath !== null && file_exists($this->logoPath)) {
                $pngData = $this->applyLogo($pngData);
            }

            if ($this->overlayText !== null && $this->overlayText !== '') {
                $pngData = $this->applyTextOverlay($pngData);
            }

            $this->rawResult = $pngData;
        }

        return $this;
    }

    public function getRaw(): string
    {
        if ($this->rawResult === '') {
            throw new RuntimeException('QR code has not been generated yet. Call generate() first.');
        }

        return $this->rawResult;
    }

    public function toDataUri(): string
    {
        $raw = $this->getRaw();
        $mime = $this->format->getMimeType();

        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    public function download(?string $fileName = null): StreamedResponse
    {
        $name = $fileName ?? $this->fileName;
        $extension = $this->format->getExtension();
        $mime = $this->format->getMimeType();
        $content = $this->getRaw();

        return Response::streamDownload(
            callback: static function () use ($content): void {
                echo $content;
            },
            name: "{$name}.{$extension}",
            headers: ['Content-Type' => $mime],
            disposition: 'attachment'
        );
    }

    public function stream(?string $fileName = null): StreamedResponse
    {
        $name = $fileName ?? $this->fileName;
        $extension = $this->format->getExtension();
        $mime = $this->format->getMimeType();
        $content = $this->getRaw();

        return Response::streamDownload(
            callback: static function () use ($content): void {
                echo $content;
            },
            name: "{$name}.{$extension}",
            headers: ['Content-Type' => $mime],
            disposition: 'inline'
        );
    }

    protected function applyLogo(string $qrPngData): string
    {
        if (extension_loaded('imagick')) {
            $qr = new Imagick();
            $qr->readImageBlob($qrPngData);

            $logo = new Imagick($this->logoPath);
            $logo->thumbnailImage($this->logoSize, $this->logoSize, true);

            $x = (int) (($qr->getImageWidth() - $logo->getImageWidth()) / 2);
            $y = (int) (($qr->getImageHeight() - $logo->getImageHeight()) / 2);

            $qr->compositeImage($logo, Imagick::COMPOSITE_OVER, $x, $y);
            $result = $qr->getImageBlob();

            $qr->destroy();
            $logo->destroy();

            return $result;
        }

        // GD fallback
        $qrImage = imagecreatefromstring($qrPngData);
        if ($qrImage === false) {
            return $qrPngData;
        }

        $logoData = file_get_contents((string) $this->logoPath);
        if ($logoData === false) {
            imagedestroy($qrImage);
            return $qrPngData;
        }

        $logoImage = imagecreatefromstring($logoData);
        if ($logoImage === false) {
            imagedestroy($qrImage);
            return $qrPngData;
        }

        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

        $dstX = (int) (($qrWidth - $this->logoSize) / 2);
        $dstY = (int) (($qrHeight - $this->logoSize) / 2);

        imagecopyresampled(
            $qrImage,
            $logoImage,
            $dstX,
            $dstY,
            0,
            0,
            $this->logoSize,
            $this->logoSize,
            $logoWidth,
            $logoHeight
        );

        ob_start();
        imagepng($qrImage);
        $result = (string) ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($logoImage);

        return $result;
    }

    protected function applyTextOverlay(string $qrPngData): string
    {
        if ($this->overlayText === null || $this->overlayText === '') {
            return $qrPngData;
        }

        if (extension_loaded('imagick')) {
            $imagick = new Imagick();
            $imagick->readImageBlob($qrPngData);

            $qrWidth = $imagick->getImageWidth();
            $qrHeight = $imagick->getImageHeight();

            $draw = new ImagickDraw();
            $draw->setFontSize($this->fontSize);
            $draw->setFillColor(new ImagickPixel($this->fontColor));
            $draw->setTextAlignment(Imagick::ALIGN_CENTER);

            $metrics = $imagick->queryFontMetrics($draw, $this->overlayText);
            $textHeight = (int) $metrics['textHeight'];

            $padding = 12;
            $newHeight = $qrHeight + $textHeight + $padding;

            $canvas = new Imagick();
            $canvas->newImage($qrWidth, $newHeight, new ImagickPixel($this->backgroundColor));
            $canvas->setImageFormat('png');

            $canvas->compositeImage($imagick, Imagick::COMPOSITE_OVER, 0, 0);

            $textY = $qrHeight + (int) ($textHeight * 0.8);
            $canvas->annotateImage($draw, (float) ($qrWidth / 2), (float) $textY, 0.0, $this->overlayText);

            $imagick->destroy();
            $result = $canvas->getImageBlob();
            $canvas->destroy();

            return $result;
        }

        // GD fallback
        $qrImage = imagecreatefromstring($qrPngData);
        if ($qrImage === false) {
            return $qrPngData;
        }

        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        $fontId = min(5, max(1, (int) floor($this->fontSize / 4)));
        $textWidth = strlen($this->overlayText) * imagefontwidth($fontId);
        $textHeight = imagefontheight($fontId);
        $padding = 10;

        $newHeight = max(1, $qrHeight + $textHeight + $padding);
        $canvas = imagecreatetruecolor(max(1, $qrWidth), $newHeight);
        if ($canvas === false) {
            imagedestroy($qrImage);
            return $qrPngData;
        }

        $bgRgb = $this->hexToRgb($this->backgroundColor);
        $bg = imagecolorallocate($canvas, $bgRgb['r'], $bgRgb['g'], $bgRgb['b']);
        if ($bg !== false) {
            imagefill($canvas, 0, 0, $bg);
        }

        imagecopy($canvas, $qrImage, 0, 0, 0, 0, $qrWidth, $qrHeight);

        $fgRgb = $this->hexToRgb($this->fontColor);
        $textColor = imagecolorallocate($canvas, $fgRgb['r'], $fgRgb['g'], $fgRgb['b']);

        $x = (int) max(0, ($qrWidth - $textWidth) / 2);
        $y = $qrHeight + 2;

        if ($textColor !== false) {
            imagestring($canvas, $fontId, $x, $y, $this->overlayText, $textColor);
        }

        ob_start();
        imagepng($canvas);
        $result = (string) ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($canvas);

        return $result;
    }

    /**
     * @return array{r: int<0, 255>, g: int<0, 255>, b: int<0, 255>}
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = min(255, max(0, (int) hexdec(substr($hex, 0, 2))));
        $g = min(255, max(0, (int) hexdec(substr($hex, 2, 2))));
        $b = min(255, max(0, (int) hexdec(substr($hex, 4, 2))));

        return [
            'r' => $r,
            'g' => $g,
            'b' => $b,
        ];
    }
}
