<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Infolists\Components;

use Closure;
use Filament\Infolists\Components\Entry;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrRendering;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Support\QrRenderSpec;

class QrEntry extends Entry
{
    use HasQrRendering;

    protected string $view = 'filament-qr-code::components.qr-entry';

    protected string|Closure|null $qrData = null;

    protected int|Closure $size = 200;

    protected int|Closure $margin = 2;

    protected QrFormat|string|Closure $format = QrFormat::Svg;

    protected string|Closure $foregroundColor = '#000000';

    protected string|Closure $backgroundColor = '#ffffff';

    protected ?string $captionText = null;

    protected string|Closure|null $errorCorrectionLevel = null;

    protected string|Closure|null $logoPath = null;

    protected int|Closure $logoSize = 50;

    protected bool|Closure $canDownload = true;

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

    public function size(int|Closure $size): static
    {
        $this->size = $size;

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

    public function caption(?string $text): static
    {
        $this->captionText = $text;

        return $this;
    }

    public function getCaption(): ?string
    {
        return $this->captionText;
    }

    public function downloadable(bool|Closure $condition = true): static
    {
        $this->canDownload = $condition;

        return $this;
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

    public function getRenderSpec(): QrRenderSpec
    {
        $format = $this->evaluate($this->format);

        return new QrRenderSpec(
            size: (int) $this->evaluate($this->size),
            margin: (int) $this->evaluate($this->margin),
            foreground: (string) $this->evaluate($this->foregroundColor),
            background: (string) $this->evaluate($this->backgroundColor),
            format: $format instanceof QrFormat || is_string($format) ? $format : null,
            errorCorrection: $this->errorCorrectionLevel === null ? null : (string) $this->evaluate($this->errorCorrectionLevel),
            logoPath: $this->logoPath === null ? null : (string) $this->evaluate($this->logoPath),
            logoSize: (int) $this->evaluate($this->logoSize),
            caption: $this->captionText,
        );
    }

    public function getQrDataUri(): string
    {
        $data = $this->getQrData();
        if ($data === null || $data === '') {
            return '';
        }

        return $this->getRenderSpec()->toDataUri($data);
    }

    public function isDownloadable(): bool
    {
        return (bool) $this->evaluate($this->canDownload);
    }
}
