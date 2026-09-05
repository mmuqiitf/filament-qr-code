<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

class QrColumn extends Column
{
    protected string $view = 'filament-qr-code::components.qr-column';

    protected string|Closure|null $qrData = null;

    protected int|Closure $thumbnailSize = 48;

    protected int|Closure $modalSize = 250;

    protected int|Closure $margin = 1;

    protected QrFormat|string|Closure $format = QrFormat::Svg;

    protected string|Closure $foregroundColor = '#000000';

    protected string|Closure $backgroundColor = '#ffffff';

    protected bool|Closure $canPreview = true;

    protected bool|Closure $canDownload = true;

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

        return QrCodeService::make()
            ->size((int) $this->evaluate($this->thumbnailSize))
            ->margin((int) $this->evaluate($this->margin))
            ->color((string) $this->evaluate($this->foregroundColor))
            ->backgroundColor((string) $this->evaluate($this->backgroundColor))
            ->generate($data)
            ->toDataUri();
    }

    public function getModalDataUri(): string
    {
        $data = $this->getQrData();
        if ($data === null || $data === '') {
            return '';
        }

        return QrCodeService::make()
            ->size((int) $this->evaluate($this->modalSize))
            ->margin(2)
            ->color((string) $this->evaluate($this->foregroundColor))
            ->backgroundColor((string) $this->evaluate($this->backgroundColor))
            ->generate($data)
            ->toDataUri();
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
}
