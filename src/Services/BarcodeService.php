<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Services;

use Illuminate\Support\Facades\Response;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeRenderer;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeRenderOptions;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeSymbol;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderCache;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Fluent builder for linear barcodes (Code 128, EAN-13, EAN-8, UPC-A,
 * Code 39, ITF-14, GS1-128), the barcode counterpart of QrCodeService.
 */
class BarcodeService
{
    protected BarcodeType $type = BarcodeType::Code128;

    protected QrFormat $format = QrFormat::Svg;

    protected int $barWidth = 2;

    protected int $height = 60;

    protected ?int $margin = null;

    protected string $foregroundColor = '#000000';

    protected string $backgroundColor = '#ffffff';

    protected bool $showText = true;

    protected int $fontSize = 14;

    protected ?string $fontPath = null;

    protected string $fileName = 'barcode';

    protected string $rawResult = '';

    protected string $text = '';

    /** Start a fresh barcode render. */
    public static function make(): self
    {
        return new self;
    }

    /** Symbology to draw; Code 128 by default. */
    public function type(BarcodeType|string $type): self
    {
        $this->type = $type instanceof BarcodeType ? $type : BarcodeType::from(strtolower($type));

        return $this;
    }

    /** Output SVG (default) or PNG. */
    public function format(QrFormat|string $format): self
    {
        $this->format = is_string($format) ? (QrFormat::tryFrom(strtolower($format)) ?? QrFormat::Svg) : $format;

        return $this;
    }

    /** Width in pixels of the narrowest bar (one module). */
    public function barWidth(int $pixels): self
    {
        $this->barWidth = max(1, $pixels);

        return $this;
    }

    /** Bar height in pixels, not counting the text line. */
    public function height(int $pixels): self
    {
        $this->height = max(10, $pixels);

        return $this;
    }

    /** Quiet zone in modules on each side; null uses the type default (10, or 0 for ITF-14). */
    public function margin(?int $modules): self
    {
        $this->margin = $modules === null ? null : max(0, $modules);

        return $this;
    }

    /** Bar and text colour as hex. */
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

    /** Print the human-readable value under the bars (on by default). */
    public function showText(bool $condition = true): self
    {
        $this->showText = $condition;

        return $this;
    }

    /** Font size in pixels of the human-readable text. */
    public function fontSize(int $pixels): self
    {
        $this->fontSize = max(6, $pixels);

        return $this;
    }

    /** TrueType font file for the PNG text line (Imagick only; GD uses a bitmap font). */
    public function font(?string $fontPath): self
    {
        $this->fontPath = $fontPath;

        return $this;
    }

    /** File name used by download() and stream(). */
    public function fileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Encode and render the value.
     *
     * @throws InvalidBarcodeException when the value doesn't fit the type
     */
    public function generate(string $data): self
    {
        $options = $this->toRenderOptions($data);

        $this->text = BarcodeSymbol::encode($this->type, $data)->text;
        $this->rawResult = QrRenderCache::remember(
            $options->cacheKey(),
            static fn (): string => (new BarcodeRenderer)->render($options),
        );

        return $this;
    }

    /**
     * Check a value without rendering it.
     *
     * @throws InvalidBarcodeException
     */
    public static function validate(BarcodeType $type, string $data): void
    {
        BarcodeSymbol::encode($type, $data);
    }

    public function getType(): BarcodeType
    {
        return $this->type;
    }

    public function getFormat(): QrFormat
    {
        return $this->format;
    }

    /** The human-readable value, check digit included (after generate()). */
    public function getText(): string
    {
        return $this->text;
    }

    /** Accessible name, e.g. "EAN-13 barcode: 4006381333931" (after generate()). */
    public function getAccessibleName(): string
    {
        return "{$this->type->getLabel()} barcode: {$this->text}";
    }

    /** The raw SVG or PNG bytes. */
    public function getRaw(): string
    {
        if ($this->rawResult === '') {
            throw new RuntimeException('Barcode has not been generated yet. Call generate() first.');
        }

        return $this->rawResult;
    }

    /** Render the barcode as a `data:` URI for `<img src>`. */
    public function toDataUri(): string
    {
        return 'data:'.$this->format->getMimeType().';base64,'.base64_encode($this->getRaw());
    }

    /** Render the barcode as a download response. */
    public function download(?string $fileName = null): StreamedResponse
    {
        return $this->respond($fileName, 'attachment');
    }

    /** Render the barcode as an inline image response. */
    public function stream(?string $fileName = null): StreamedResponse
    {
        return $this->respond($fileName, 'inline');
    }

    /** Clear the in-process render cache (shared with QR codes). */
    public static function flushRenderCache(): void
    {
        QrRenderCache::flushMemory();
    }

    protected function toRenderOptions(string $data): BarcodeRenderOptions
    {
        return new BarcodeRenderOptions(
            data: $data,
            type: $this->type,
            format: $this->format,
            barWidth: $this->barWidth,
            height: $this->height,
            margin: $this->margin ?? $this->type->getDefaultMargin(),
            foregroundColor: $this->foregroundColor,
            backgroundColor: $this->backgroundColor,
            showText: $this->showText,
            fontSize: $this->fontSize,
            fontPath: $this->fontPath,
        );
    }

    protected function respond(?string $fileName, string $disposition): StreamedResponse
    {
        $content = $this->getRaw();

        return Response::streamDownload(
            callback: static function () use ($content): void {
                echo $content;
            },
            name: ($fileName ?? $this->fileName).'.'.$this->format->getExtension(),
            headers: ['Content-Type' => $this->format->getMimeType()],
            disposition: $disposition,
        );
    }
}
