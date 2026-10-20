<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasCodeNormalization;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasOptionsSync;
use Mmuqiitf\FilamentQrCode\Concerns\HasOutcomeFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasRenderHookScopes;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanMode;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Concerns\HasSequentialScan;
use Mmuqiitf\FilamentQrCode\Concerns\InteractsWithScans;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;

class QrScanner extends Field
{
    use CanBeReadOnly;
    use HasCameraScanning;
    use HasCodeNormalization;
    use HasFeedback;
    use HasHardwareScanner;
    use HasOptionsSync;
    use HasOutcomeFeedback;
    use HasPlaceholder;
    use HasRenderHookScopes;
    use HasScanMode;
    use HasScanPayload;
    use HasSequentialScan;
    use InteractsWithScans;

    protected string $view = 'filament-qr-code::components.qr-scanner';

    protected bool|Closure $allowUpload = true;

    protected ?Closure $scanFormatter = null;

    protected ?Closure $onScanCallback = null;

    protected HardwareInputScope|Closure|null $hardwareInputScope = null;

    /**
     * Focus: wedge scans reach this field only while it has focus (default).
     * Page: the field also catches wedge scans when no scan field has focus.
     */
    public function hardwareInput(
        HardwareInputScope|Closure $scope = HardwareInputScope::Focus,
        bool|Closure $enabled = true,
    ): static {
        $this->hardwareInputScope = $scope;
        $this->isHardwareScannerEnabled = $enabled;

        return $this;
    }

    public function getHardwareInputScope(): HardwareInputScope
    {
        if ($this->hardwareInputScope === null) {
            $configured = function_exists('config') ? config('qr-code.hardware_scanner.default_scope', 'focus') : 'focus';

            return is_string($configured)
                ? (HardwareInputScope::tryFrom($configured) ?? HardwareInputScope::Focus)
                : HardwareInputScope::Focus;
        }

        $scope = $this->evaluate($this->hardwareInputScope);

        return $scope instanceof HardwareInputScope ? $scope : HardwareInputScope::Focus;
    }

    /** @return array<string, mixed> */
    public function getComponentScanPayload(): array
    {
        return [
            'hardwareScope' => $this->getHardwareInputScope()->value,
            ...$this->getScanModeScanPayload(),
        ];
    }

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
     * Submit-time validation rules for scanned values. Live scans write
     * state directly, so these run on submit like any other field rules —
     * pair with rejectWhen() for immediate feedback.
     *
     * @param  array<int, string>|Closure  $rules
     */
    public function scanRules(array|Closure $rules): static
    {
        $this->rules($rules);

        return $this;
    }

    /**
     * Immediately reject a live scan matching $predicate: the state is
     * cleared and a `qr-scan-rejected` browser event (with message) is
     * dispatched so the app can notify the operator.
     */
    public function rejectWhen(Closure $predicate, string|Closure $message): static
    {
        $this->afterStateUpdated(function ($component, $state, $livewire) use ($predicate, $message): void {
            if (blank($state)) {
                return;
            }

            $rejected = (bool) $component->evaluate($predicate, [
                'rawValue' => $state,
                'state' => $state,
            ]);

            if (! $rejected) {
                return;
            }

            $component->state(null);

            $livewire->dispatch('qr-scan-rejected', message: (string) $component->evaluate($message, [
                'rawValue' => $state,
                'state' => $state,
            ]));
        });

        return $this;
    }

    /**
     * Live-scan normalizer (runs on every Livewire state update, including
     * camera and hardware scans). Shorthand for afterStateUpdated() so the
     * live hook is discoverable next to the programmatic-only scanFormat().
     */
    public function normalizeUsing(Closure $normalizer): static
    {
        // Live scans: applied in handleScan() before rules and lookup.
        $this->scanNormalizer = $normalizer;

        $this->afterStateUpdated(function ($component, $state) use ($normalizer): void {
            $normalized = $component->evaluate($normalizer, [
                'code' => $state,
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
        event(new QrCodeScanned(
            code: $scannedValue,
            source: 'scanner-field',
            field: $this->getName(),
        ));

        if ($this->onScanCallback instanceof Closure) {
            $this->evaluate($this->onScanCallback, ['scannedValue' => $scannedValue, 'component' => $this]);
        }
    }
}
