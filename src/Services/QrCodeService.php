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
use Mmuqiitf\FilamentQrCode\Support\LogoPathGuard;
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

    /**
     * Override generator.cache_ttl at runtime: seconds to keep persistent (L2)
     * entries, false to turn the L2 cache off, null to clear the override and
     * use the config again.
     */
    public static function cacheTtl(int|false|null $seconds): void
    {
        QrRenderCache::setTtl($seconds);
    }

    /** Laravel cache store for the persistent render cache; null uses the default store. */
    public static function cacheStore(?string $store): void
    {
        QrRenderCache::setStore($store);
    }

    /**
     * The persistent (L2) cache TTL in effect: the cacheTtl() override, else
     * generator.cache_ttl. An int is seconds, null means no expiry, and false
     * means the L2 cache is off (the in-process cache still works).
     */
    public static function effectiveCacheTtl(): int|false|null
    {
        return QrRenderCache::effectiveTtl();
    }

    /** Create a renderer seeded with the `generator` config defaults. */
    public function __construct()
    {
        $this->errorCorrectionLevel = ErrorCorrectionLevel::M();
    }

    /** Start a fresh render (same as the `FilamentQrCode` facade). */
    public static function make(): self
    {
        return new self;
    }

    /** Image size in pixels. */
    public function size(int $size): self
    {
        $this->size = max(50, $size);

        return $this;
    }

    /** Quiet-zone margin in modules. */
    public function margin(int $margin): self
    {
        $this->margin = max(0, $margin);

        return $this;
    }

    /** Output SVG or PNG. */
    public function format(QrFormat|string $format): self
    {
        if (is_string($format)) {
            $format = QrFormat::tryFrom(strtolower($format)) ?? QrFormat::Svg;
        }

        $this->format = $format;

        return $this;
    }

    /** Foreground colour as hex. */
    public function color(string $hexColor): self
    {
        $this->foregroundColor = $hexColor;

        return $this;
    }

    /** Background colour as hex. */
    public function backgroundColor(string $hexColor): self
    {
        $this->backgroundColor = $hexColor;

        return $this;
    }

    /** Error-correction level: L, M, Q or H. */
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

    /** Text overlay under the QR (forces PNG; falls back to GD bitmap fonts). */
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

    /** TrueType font file for withText() overlays. */
    public function font(string $fontPath): self
    {
        $this->fontPath = $fontPath;

        return $this;
    }

    /** Embed a logo image in the centre (forces PNG). */
    public function logo(string $path, int $size = 50): self
    {
        $this->logoPath = $path;
        $this->logoSize = $size;
        $this->errorCorrectionLevel = ErrorCorrectionLevel::H();
        $this->format = QrFormat::Png;

        return $this;
    }

    /** File name used by download() and stream(). */
    public function fileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /** Render the QR and return the raw SVG or PNG bytes. */
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
            logoPath: $this->guardedLogoPath(),
            logoSize: $this->logoSize,
        );
    }

    /**
     * The logo path, or null when `logo_base_paths` rejects it. Inline
     * renders degrade gracefully: the QR is drawn without the logo.
     */
    protected function guardedLogoPath(): ?string
    {
        if ($this->logoPath === null || $this->logoPath === '' || LogoPathGuard::allows($this->logoPath)) {
            return $this->logoPath;
        }

        if (function_exists('logger')) {
            logger()->warning('filament-qr-code: logo skipped because it is outside qr-code.generator.logo_base_paths.', [
                'logo' => $this->logoPath,
            ]);
        }

        return null;
    }

    /** Clear the in-process render cache. */
    public static function flushRenderCache(): void
    {
        QrRenderCache::flushMemory();
    }

    /** Clear the persistent (Laravel cache) render cache. */
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

    /** Render the QR as a `data:` URI for `<img src>`. */
    public function toDataUri(): string
    {
        $raw = $this->getRaw();
        $mime = $this->format->getMimeType();

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }

    /** Render the QR as a download response. */
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

    /** Render the QR as an inline image response. */
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
