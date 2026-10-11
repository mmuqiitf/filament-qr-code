<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Illuminate\Support\Facades\URL;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrRendering;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Mmuqiitf\FilamentQrCode\Support\QrRenderSpec;

class QrColumn extends Column
{
    use HasQrRendering;

    protected string $view = 'filament-qr-code::components.qr-column';

    protected string|Closure|null $qrData = null;

    protected int|Closure $thumbnailSize = 48;

    protected int|Closure $modalSize = 250;

    protected int|Closure $margin = 1;

    protected QrFormat|string|Closure $format = QrFormat::Svg;

    protected string|Closure $foregroundColor = '#000000';

    protected string|Closure $backgroundColor = '#ffffff';

    protected string|Closure|null $errorCorrectionLevel = null;

    protected string|Closure|null $logoPath = null;

    protected int|Closure $logoSize = 50;

    protected bool|Closure $canPreview = true;

    protected bool|Closure $canDownload = true;

    protected bool|Closure $lazyModal = true;

    public function errorCorrection(string|Closure|null $level): static
    {
        $this->errorCorrectionLevel = $level;

        return $this;
    }

    public function logo(string|Closure|null $path, int|Closure $size = 50): static
    {
        $this->logoPath = $path;
        $this->logoSize = $size;

        return $this;
    }

    public function data(string|Closure|null $data): static
    {
        $this->qrData = $data;

        return $this;
    }

    public function thumbnailSize(int|Closure $size): static
    {
        $this->thumbnailSize = $size;

        return $this;
    }

    public function size(int|Closure $size): static
    {
        return $this->thumbnailSize($size);
    }

    public function modalSize(int|Closure $size): static
    {
        $this->modalSize = $size;

        return $this;
    }

    public function margin(int|Closure $margin): static
    {
        $this->margin = $margin;

        return $this;
    }

    public function format(QrFormat|string|Closure $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function color(string|Closure $hexColor): static
    {
        $this->foregroundColor = $hexColor;

        return $this;
    }

    public function backgroundColor(string|Closure $hexColor): static
    {
        $this->backgroundColor = $hexColor;

        return $this;
    }

    public function previewable(bool|Closure $condition = true): static
    {
        $this->canPreview = $condition;

        return $this;
    }

    public function downloadable(bool|Closure $condition = true): static
    {
        $this->canDownload = $condition;

        return $this;
    }

    /**
     * Load the large preview on demand via a signed image URL instead of
     * inlining a second base64 data-URI per row. Disable with
     * ->lazyModal(false) to restore the eager (BC) behavior.
     */
    public function lazyModal(bool|Closure $condition = true): static
    {
        $this->lazyModal = $condition;

        return $this;
    }

    public function isLazyModal(): bool
    {
        return (bool) $this->evaluate($this->lazyModal);
    }

    public function getQrData(): ?string
    {
        $data = $this->evaluate($this->qrData);
        if ($data !== null) {
            return (string) $data;
        }

        $state = $this->getState();

        return is_scalar($state) ? (string) $state : null;
    }

    public function getThumbnailDataUri(): string
    {
        $data = $this->getQrData();
        if ($data === null || $data === '') {
            return '';
        }

        return $this->buildDataUri($data, (int) $this->evaluate($this->thumbnailSize), (int) $this->evaluate($this->margin));
    }

    public function getModalDataUri(): string
    {
        $data = $this->getQrData();
        if ($data === null || $data === '') {
            return '';
        }

        return $this->buildDataUri($data, (int) $this->evaluate($this->modalSize), (int) $this->evaluate($this->margin));
    }

    /**
     * Signed on-demand URL for the large preview. Tables render only the
     * small thumbnail inline; the modal image downloads when opened, so a
     * 25-row page encodes 25 small QRs instead of 50 mixed-size ones.
     *
     * The signed params carry the full render spec (logo included), so the
     * lazy preview matches the eager render exactly.
     */
    public function getModalUrl(): string
    {
        $data = $this->getQrData();
        if ($data === null || $data === '') {
            return '';
        }

        try {
            return URL::signedRoute(
                'filament-qr-code.image',
                $this->getRenderSpec((int) $this->evaluate($this->modalSize))->toSignedParams($data),
            );
        } catch (\Throwable) {
            return $this->getModalDataUri();
        }
    }

    public static function flushDataUriCache(): void
    {
        QrCodeService::flushRenderCache();
    }

    public function getRenderSpec(int $size): QrRenderSpec
    {
        $format = $this->evaluate($this->format);

        return new QrRenderSpec(
            size: $size,
            margin: (int) $this->evaluate($this->margin),
            foreground: (string) $this->evaluate($this->foregroundColor),
            background: (string) $this->evaluate($this->backgroundColor),
            format: $format instanceof QrFormat || is_string($format) ? $format : null,
            errorCorrection: $this->errorCorrectionLevel === null ? null : (string) $this->evaluate($this->errorCorrectionLevel),
            logoPath: $this->logoPath === null ? null : (string) $this->evaluate($this->logoPath),
            logoSize: (int) $this->evaluate($this->logoSize),
        );
    }

    protected function buildDataUri(string $data, int $size, int $margin): string
    {
        return $this->getRenderSpec($size)->toDataUri($data);
    }

    public function isPreviewable(): bool
    {
        return (bool) $this->evaluate($this->canPreview);
    }

    public function isDownloadable(): bool
    {
        return (bool) $this->evaluate($this->canDownload);
    }

    public function getThumbnailSize(): int
    {
        return (int) $this->evaluate($this->thumbnailSize);
    }

    public function getModalExtension(): string
    {
        $format = $this->evaluate($this->format);

        if ($format instanceof QrFormat) {
            return $format->getExtension();
        }

        if (is_string($format)) {
            return strtolower($format) === 'png' ? 'png' : 'svg';
        }

        return 'svg';
    }
}
