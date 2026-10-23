<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Response;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Rendering\HexColor;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderCache;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderer;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderOptions;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fluent facade over the rendering pipeline (QrRenderer, QrRenderCache,
 * compositors). Holds configuration only; rendering lives in the Rendering namespace.
 */
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

    protected ?string $fontPath = null;

    protected ?string $logoPath = null;

    protected int $logoSize = 50;

    protected string $fileName = 'qrcode';

    /**
     * Toggle the persistent (Laravel Cache) L2 layer. The in-process L1
     * stays active regardless so repeated renders in one request encode once.
     */
    public static function persistentCache(bool $enabled = true): void
    {
        QrRenderCache::enablePersistent($enabled);
    }

    public static function cacheTtl(?int $seconds): void
    {
        QrRenderCache::setTtl($seconds);
    }

    public static function cacheStore(?string $store): void
    {
        QrRenderCache::setStore($store);
    }

    public static function effectiveCacheTtl(): ?int
    {
        return QrRenderCache::effectiveTtl();
    }

    public function __construct()
    {
        $this->errorCorrectionLevel = ErrorCorrectionLevel::M();
    }

    public static function make(): self
    {
        return new self;
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

    public function withText(string $text, int $fontSize = 16, string $fontColor = '#000000', ?string $fontPath = null): self
    {
        $this->overlayText = $text;
        $this->fontSize = $fontSize;
        $this->fontColor = $fontColor;
        $this->fontPath = $fontPath;

        // Overlay text requires PNG format for bitmap rasterization
        $this->format = QrFormat::Png;

        return $this;
    }

    public function font(string $fontPath): self
    {
        $this->fontPath = $fontPath;

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

        // Text overlays and logos are raster operations: they require PNG
        // regardless of the order format()/withText()/logo() were called in.
        if ($this->format === QrFormat::Svg && ($this->overlayText !== null || $this->logoPath !== null)) {
            $this->format = QrFormat::Png;
        }

        $options = $this->toRenderOptions($data);

        // Render output is fully determined by the options, so identical
        // payloads (e.g. repeated table rows or thumbnail + modal previews)
        // share one cached render instead of re-encoding every time.
        $this->rawResult = QrRenderCache::remember(
            $options->cacheKey(),
            static fn (): string => (new QrRenderer)->render($options),
        );

        return $this;
    }

    protected function toRenderOptions(string $data): QrRenderOptions
    {
        return new QrRenderOptions(
            data: $data,
            size: $this->size,
            margin: $this->margin,
            format: $this->format,
            foregroundColor: $this->foregroundColor,
            backgroundColor: $this->backgroundColor,
            errorCorrectionLevel: $this->errorCorrectionLevel ?? ErrorCorrectionLevel::M(),
            overlayText: $this->overlayText,
            fontSize: $this->fontSize,
            fontColor: $this->fontColor,
            fontPath: $this->fontPath,
            logoPath: $this->logoPath,
            logoSize: $this->logoSize,
        );
    }

    public static function flushRenderCache(): void
    {
        QrRenderCache::flushMemory();
    }

    public static function flushPersistentCache(): void
    {
        QrRenderCache::flushPersistent();
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

        return 'data:'.$mime.';base64,'.base64_encode($raw);
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

    /**
     * @return array{r: int<0, 255>, g: int<0, 255>, b: int<0, 255>}
     */
    protected function hexToRgb(string $hex): array
    {
        return HexColor::toRgb($hex);
    }
}
