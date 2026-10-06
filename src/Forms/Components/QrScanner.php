<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasSequentialScan;

class QrScanner extends Field
{
    use CanBeReadOnly;
    use HasCameraScanning;
    use HasFeedback;
    use HasHardwareScanner;
    use HasPlaceholder;
    use HasSequentialScan;

    protected string $view = 'filament-qr-code::components.qr-scanner';

    protected bool|Closure $allowUpload = true;

    protected ?Closure $scanFormatter = null;

    protected ?Closure $onScanCallback = null;

    public function allowUpload(bool|Closure $condition = true): static
    {
        $this->allowUpload = $condition;

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

    public function isUploadAllowed(): bool
    {
        return (bool) $this->evaluate($this->allowUpload);
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
