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

    /**
     * Programmatic-only formatter. Runs only via formatScannedValue() in
     * custom flows — never on live camera/hardware scans. For live values
     * use normalizeUsing() (or afterStateUpdated() directly).
     */
    public function scanFormat(?Closure $formatter): static
    {
        $this->scanFormatter = $formatter;

        return $this;
    }

    /**
     * Programmatic-only callback. Invoke via triggerOnScan() in custom
     * flows — live scans dispatch the `qr-scanned` window event and update
     * Livewire state directly instead.
     */
    public function onScan(?Closure $callback): static
    {
        $this->onScanCallback = $callback;

        return $this;
    }

    /**
     * Live-scan normalizer (runs on every Livewire state update, including
     * camera and hardware scans). Shorthand for afterStateUpdated() so the
     * live hook is discoverable next to the programmatic-only scanFormat().
     */
    public function normalizeUsing(Closure $normalizer): static
    {
        $this->afterStateUpdated(function ($component, $state) use ($normalizer): void {
            $normalized = $component->evaluate($normalizer, [
                'rawValue' => $state,
                'state' => $state,
            ]);

            if ($normalized !== $state) {
                $component->state($normalized);
            }
        });

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
