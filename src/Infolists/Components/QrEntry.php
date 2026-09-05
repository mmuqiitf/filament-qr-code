<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Infolists\Components;

use Closure;
use Filament\Infolists\Components\Entry;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

class QrEntry extends Entry
{
    protected string $view = 'filament-qr-code::components.qr-entry';

    protected string|Closure|null $qrData = null;

    protected int|Closure $size = 200;

    protected int|Closure $margin = 2;

    protected QrFormat|string|Closure $format = QrFormat::Svg;

    protected string|Closure $foregroundColor = '#000000';

    protected string|Closure $backgroundColor = '#ffffff';

    protected ?string $captionText = null;

    protected bool|Closure $canDownload = true;

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

    public function getQrDataUri(): string
    {
        $data = $this->getQrData();
        if ($data === null || $data === '') {
            return '';
        }

        $service = QrCodeService::make()
            ->size((int) $this->evaluate($this->size))
            ->margin((int) $this->evaluate($this->margin))
            ->color((string) $this->evaluate($this->foregroundColor))
            ->backgroundColor((string) $this->evaluate($this->backgroundColor));

        $format = $this->evaluate($this->format);
        if ($format instanceof QrFormat) {
            $service->format($format);
        } elseif (is_string($format)) {
            $service->format($format);
        }

        if ($this->captionText !== null) {
            $service->withText($this->captionText);
        }

        $service->generate($data);

        return $service->toDataUri();
    }

    public function isDownloadable(): bool
    {
        return (bool) $this->evaluate($this->canDownload);
    }
}
