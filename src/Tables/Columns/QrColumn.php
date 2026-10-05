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

    protected string|Closure|null $errorCorrectionLevel = null;

    protected string|Closure|null $logoPath = null;

    protected int|Closure $logoSize = 50;

    protected bool|Closure $canPreview = true;

    protected bool|Closure $canDownload = true;

    /**
     * @var array<string, string>
     */
    protected static array $dataUriCache = [];

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

    public static function flushDataUriCache(): void
    {
        static::$dataUriCache = [];
    }

    protected function buildDataUri(string $data, int $size, int $margin): string
    {
        $format = $this->evaluate($this->format);
        $formatValue = $format instanceof QrFormat ? $format->value : (string) $format;
        $foreground = (string) $this->evaluate($this->foregroundColor);
        $background = (string) $this->evaluate($this->backgroundColor);
        $errorCorrection = $this->errorCorrectionLevel === null ? '' : (string) $this->evaluate($this->errorCorrectionLevel);
        $logoPath = $this->logoPath === null ? '' : (string) $this->evaluate($this->logoPath);
        $logoSize = (int) $this->evaluate($this->logoSize);

        $cacheKey = md5(implode('|', [$data, $size, $margin, $formatValue, $foreground, $background, $errorCorrection, $logoPath, $logoSize]));

        if (isset(static::$dataUriCache[$cacheKey])) {
            return static::$dataUriCache[$cacheKey];
        }

        $service = QrCodeService::make()
            ->size($size)
            ->margin($margin)
            ->color($foreground)
            ->backgroundColor($background);

        if ($format instanceof QrFormat) {
            $service->format($format);
        } elseif (is_string($format)) {
            $service->format($format);
        }

        if ($errorCorrection !== '') {
            $service->errorCorrection($errorCorrection);
        }

        if ($logoPath !== '') {
            $service->logo($logoPath, $logoSize);
        }

        $uri = $service->generate($data)->toDataUri();

        if (count(static::$dataUriCache) >= 200) {
            array_shift(static::$dataUriCache);
        }

        static::$dataUriCache[$cacheKey] = $uri;

        return $uri;
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
