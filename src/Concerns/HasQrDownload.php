<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Support\QrRenderSpec;

trait HasQrDownload
{
    protected string|Closure|null $qrData = null;

    protected string|Closure|null $qrFileName = null;

    protected QrFormat|string|Closure $qrFormat = QrFormat::Svg;

    protected int|Closure $qrImageSize = 400;

    protected int|Closure $qrMargin = 2;

    protected string|Closure $qrForegroundColor = '#000000';

    protected string|Closure $qrBackgroundColor = '#ffffff';

    protected string|Closure|null $qrErrorCorrection = null;

    protected string|Closure|null $qrLogoPath = null;

    protected int|Closure $qrLogoSize = 50;

    protected string|Closure|null $qrCaption = null;

    public function qrData(string|Closure|null $data): static
    {
        $this->qrData = $data;

        return $this;
    }

    public function qrFileName(string|Closure|null $name): static
    {
        $this->qrFileName = $name;

        return $this;
    }

    public function qrFormat(QrFormat|string|Closure $format): static
    {
        $this->qrFormat = $format;

        return $this;
    }

    public function qrImageSize(int|Closure $size): static
    {
        $this->qrImageSize = $size;

        return $this;
    }

    public function qrMargin(int|Closure $margin): static
    {
        $this->qrMargin = $margin;

        return $this;
    }

    public function qrColor(string|Closure $hexColor): static
    {
        $this->qrForegroundColor = $hexColor;

        return $this;
    }

    public function qrBackgroundColor(string|Closure $hexColor): static
    {
        $this->qrBackgroundColor = $hexColor;

        return $this;
    }

    public function qrErrorCorrection(string|Closure|null $level): static
    {
        $this->qrErrorCorrection = $level;

        return $this;
    }

    public function qrLogo(string|Closure|null $path, int|Closure $size = 50): static
    {
        $this->qrLogoPath = $path;
        $this->qrLogoSize = $size;

        return $this;
    }

    public function qrCaption(string|Closure|null $text): static
    {
        $this->qrCaption = $text;

        return $this;
    }

    /**
     * One render spec for every download, so single and bulk exports can
     * never diverge in options again. Defaults preserve the historic
     * black-on-white output; callers opt into colors, logo, and caption.
     */
    public function getDownloadSpec(mixed $record = null): QrRenderSpec
    {
        $format = $this->evaluate($this->qrFormat, ['record' => $record]);

        return new QrRenderSpec(
            size: (int) $this->evaluate($this->qrImageSize, ['record' => $record]),
            margin: (int) $this->evaluate($this->qrMargin, ['record' => $record]),
            foreground: (string) $this->evaluate($this->qrForegroundColor, ['record' => $record]),
            background: (string) $this->evaluate($this->qrBackgroundColor, ['record' => $record]),
            format: $format instanceof QrFormat || is_string($format) ? $format : null,
            errorCorrection: $this->qrErrorCorrection === null ? null : (string) $this->evaluate($this->qrErrorCorrection, ['record' => $record]),
            logoPath: $this->qrLogoPath === null ? null : (string) $this->evaluate($this->qrLogoPath, ['record' => $record]),
            logoSize: (int) $this->evaluate($this->qrLogoSize, ['record' => $record]),
            caption: $this->qrCaption === null ? null : (string) $this->evaluate($this->qrCaption, ['record' => $record]),
        );
    }
}
