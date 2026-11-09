<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;
use Mmuqiitf\FilamentQrCode\Support\BarcodeRenderSpec;

/**
 * Shared setters and rendering for BarcodeDisplay, BarcodeEntry and
 * BarcodeColumn. Invalid values never throw while rendering: the view gets
 * a translated inline error instead of a broken image.
 */
trait HasBarcodeOptions
{
    protected BarcodeType|string|Closure $barcodeType = BarcodeType::Code128;

    protected string|Closure|null $barcodeData = null;

    protected QrFormat|string|Closure $format = QrFormat::Svg;

    protected int|Closure|null $barWidth = null;

    protected int|Closure|null $barHeight = null;

    protected int|Closure|null $barcodeMargin = null;

    protected string|Closure $foregroundColor = '#000000';

    protected string|Closure $backgroundColor = '#ffffff';

    protected bool|Closure|null $barcodeShowText = null;

    protected bool|Closure|null $canDownload = null;

    /** Symbology to encode: a BarcodeType or its value, e.g. 'ean13'. */
    public function type(BarcodeType|string|Closure $type): static
    {
        $this->barcodeType = $type;

        return $this;
    }

    /** Value to encode; a closure receives the record. Defaults to the state. */
    public function data(string|Closure|null $data): static
    {
        $this->barcodeData = $data;

        return $this;
    }

    /** Render as SVG (default) or PNG. */
    public function format(QrFormat|string|Closure $format): static
    {
        $this->format = $format;

        return $this;
    }

    /** Width in pixels of the narrowest bar. */
    public function barWidth(int|Closure $pixels): static
    {
        $this->barWidth = $pixels;

        return $this;
    }

    /** Height in pixels of the bars, not counting the text line. */
    public function height(int|Closure $pixels): static
    {
        $this->barHeight = $pixels;

        return $this;
    }

    /** Quiet zone on each side in modules; null uses the type's default. */
    public function margin(int|Closure|null $modules): static
    {
        $this->barcodeMargin = $modules;

        return $this;
    }

    /** Bar and text colour as hex. */
    public function color(string|Closure $hexColor): static
    {
        $this->foregroundColor = $hexColor;

        return $this;
    }

    /** Background colour as hex. */
    public function backgroundColor(string|Closure $hexColor): static
    {
        $this->backgroundColor = $hexColor;

        return $this;
    }

    /** Show the human-readable value under the bars. */
    public function showText(bool|Closure $condition = true): static
    {
        $this->barcodeShowText = $condition;

        return $this;
    }

    /** Show a download link for the image. */
    public function downloadable(bool|Closure $condition = true): static
    {
        $this->canDownload = $condition;

        return $this;
    }

    /** Whether the download link is shown. */
    public function isDownloadable(): bool
    {
        return (bool) ($this->evaluate($this->canDownload) ?? $this->getDefaultDownloadable());
    }

    /** The configured symbology. */
    public function getType(): BarcodeType
    {
        $type = $this->evaluate($this->barcodeType);

        return $type instanceof BarcodeType ? $type : BarcodeType::from(strtolower((string) $type));
    }

    /** The value to encode: data() when set, else the component state. */
    public function getBarcodeData(): ?string
    {
        $data = $this->evaluate($this->barcodeData);
        if ($data !== null) {
            return (string) $data;
        }

        $state = $this->getState();

        return is_scalar($state) ? (string) $state : null;
    }

    /**
     * Render the current value.
     *
     * @throws InvalidBarcodeException when the value doesn't fit the type
     */
    public function getBarcodeService(): BarcodeService
    {
        return $this->getBarcodeRenderSpec()->toService()->generate((string) $this->getBarcodeData());
    }

    /**
     * Resolved draw options. The column signs them; forms and infolists render them inline.
     *
     * @internal Used by the package views.
     */
    public function getBarcodeRenderSpec(): BarcodeRenderSpec
    {
        $format = $this->evaluate($this->format);
        $margin = $this->evaluate($this->barcodeMargin);

        return new BarcodeRenderSpec(
            type: $this->getType(),
            format: $format instanceof QrFormat ? $format : (QrFormat::tryFrom(strtolower((string) $format)) ?? QrFormat::Svg),
            barWidth: (int) ($this->evaluate($this->barWidth) ?? $this->getDefaultBarWidth()),
            height: (int) ($this->evaluate($this->barHeight) ?? $this->getDefaultBarHeight()),
            margin: $margin === null ? null : (int) $margin,
            foreground: (string) $this->evaluate($this->foregroundColor),
            background: (string) $this->evaluate($this->backgroundColor),
            showText: (bool) ($this->evaluate($this->barcodeShowText) ?? $this->getDefaultShowText()),
        );
    }

    /**
     * View data: the image data URI ('' when empty or invalid), its alt text,
     * file extension and the translated error for an invalid value.
     * BarcodeColumn replaces the data URI with a signed image URL.
     *
     * @internal Used by the package views.
     *
     * @return array{uri: string, alt: string, extension: string, error: ?string}
     */
    public function getBarcodeRender(): array
    {
        $data = $this->getBarcodeData();
        if ($data === null || $data === '') {
            return $this->emptyBarcodeRender();
        }

        try {
            $service = $this->getBarcodeService();
        } catch (InvalidBarcodeException $e) {
            return $this->invalidBarcodeRender($e);
        }

        return [
            'uri' => $service->toDataUri(),
            'alt' => $service->getAccessibleName(),
            'extension' => $service->getFormat()->getExtension(),
            'error' => null,
        ];
    }

    /**
     * @return array{uri: string, alt: string, extension: string, error: null}
     */
    protected function emptyBarcodeRender(): array
    {
        return ['uri' => '', 'alt' => '', 'extension' => 'svg', 'error' => null];
    }

    /**
     * @return array{uri: string, alt: string, extension: string, error: string}
     */
    protected function invalidBarcodeRender(InvalidBarcodeException $e): array
    {
        return ['uri' => '', 'alt' => '', 'extension' => 'svg', 'error' => __('filament-qr-code::ui.barcode_invalid', [
            'type' => $e->type->getLabel(),
            'reason' => $e->reason,
        ])];
    }

    protected function getDefaultBarWidth(): int
    {
        return 2;
    }

    protected function getDefaultBarHeight(): int
    {
        return 80;
    }

    protected function getDefaultShowText(): bool
    {
        return true;
    }

    protected function getDefaultDownloadable(): bool
    {
        return true;
    }
}
