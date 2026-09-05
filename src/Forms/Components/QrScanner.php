<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasSequentialScan;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;

class QrScanner extends Field
{
    use CanBeReadOnly;
    use HasFeedback;
    use HasHardwareScanner;
    use HasPlaceholder;
    use HasSequentialScan;

    protected string $view = 'filament-qr-code::components.qr-scanner';

    protected int|Closure $fps = 15;

    protected int|Closure $qrbox = 250;

    protected bool|Closure $preferRearCamera = true;

    protected bool|Closure $allowUpload = true;

    /**
     * @var array<int, BarcodeFormat|string>|Closure
     */
    protected array|Closure $supportedFormats = [];

    protected ?Closure $scanFormatter = null;

    protected ?Closure $onScanCallback = null;

    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;

        return $this;
    }

    public function qrbox(int|Closure $qrbox): static
    {
        $this->qrbox = $qrbox;

        return $this;
    }

    public function preferRearCamera(bool|Closure $condition = true): static
    {
        $this->preferRearCamera = $condition;

        return $this;
    }

    public function allowUpload(bool|Closure $condition = true): static
    {
        $this->allowUpload = $condition;

        return $this;
    }

    /**
     * @param  array<int, BarcodeFormat|string>|Closure  $formats
     */
    public function formats(array|Closure $formats): static
    {
        $this->supportedFormats = $formats;

        return $this;
    }

    public function scanFormat(?Closure $formatter): static
    {
        $this->scanFormatter = $formatter;

        return $this;
    }

    public function onScan(?Closure $callback): static
    {
        $this->onScanCallback = $callback;

        return $this;
    }

    public function getFps(): int
    {
        return (int) $this->evaluate($this->fps);
    }

    public function getQrbox(): int
    {
        return (int) $this->evaluate($this->qrbox);
    }

    public function isPreferRearCamera(): bool
    {
        return (bool) $this->evaluate($this->preferRearCamera);
    }

    public function isUploadAllowed(): bool
    {
        return (bool) $this->evaluate($this->allowUpload);
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedFormats(): array
    {
        $formats = $this->evaluate($this->supportedFormats);
        if (! is_array($formats)) {
            return [];
        }

        $result = [];
        foreach ($formats as $format) {
            if ($format instanceof BarcodeFormat) {
                $result[] = $format->value;
            } elseif (is_string($format)) {
                $result[] = $format;
            }
        }

        return $result;
    }

    public function formatScannedValue(string $rawValue): mixed
    {
        if ($this->scanFormatter instanceof Closure) {
            return $this->evaluate($this->scanFormatter, ['rawValue' => $rawValue, 'state' => $rawValue]);
        }

        return $rawValue;
    }

    public function triggerOnScan(string $scannedValue): void
    {
        if ($this->onScanCallback instanceof Closure) {
            $this->evaluate($this->onScanCallback, ['scannedValue' => $scannedValue, 'component' => $this]);
        }
    }
}
