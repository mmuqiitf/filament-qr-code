<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasAnnouncements;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasCodeNormalization;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasGs1Scan;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasOptionsSync;
use Mmuqiitf\FilamentQrCode\Concerns\HasOutcomeFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasRenderHookScopes;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanMode;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Concerns\HasSequentialScan;
use Mmuqiitf\FilamentQrCode\Concerns\InteractsWithScans;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;
use Mmuqiitf\FilamentQrCode\Support\ScanMessages;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;

class QrScanner extends Field
{
    use CanBeReadOnly;
    use HasAnnouncements;
    use HasCameraScanning;
    use HasCodeNormalization;
    use HasFeedback;
    use HasGs1Scan;
    use HasHardwareScanner;
    use HasOptionsSync;
    use HasOutcomeFeedback;
    use HasPlaceholder;
    use HasRenderHookScopes;
    use HasScanMode;
    use HasScanPayload;
    use HasSequentialScan;
    use InteractsWithScans {
        acceptScan as protected acceptScanThroughState;
        rejectScan as protected rejectScanThroughState;
    }

    protected string $view = 'filament-qr-code::components.qr-scanner';

    protected bool|Closure $allowUpload = true;

    protected ?Closure $scanFormatter = null;

    protected ?Closure $onScanCallback = null;

    protected HardwareInputScope|Closure|null $hardwareInputScope = null;

    protected bool $hasRegisteredGs1Hook = false;

    /** Set while handleScan() writes an accepted scan, so the typed-value GS1 hook stays out of it. */
    protected bool $isAcceptingScan = false;

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
            ...ScanMessages::forScannerAnnouncements(),
            'fieldLabel' => $this->getAnnouncementFieldLabel(),
            'gs1' => $this->isGs1(),
            ...$this->getAnnouncementsScanPayload(),
        ];
    }

    /**
     * Read every scan as a GS1 element string (GS1-128, GS1 DataMatrix,
     * GS1 QR, raw with group separators or bracketed). The field stores the
     * normalized bracketed form, such as `(01)09506000134352(17)261231(10)ABC`.
     *
     * `$fill` maps AIs to sibling fields by relative path, like `$set`:
     * dates are written as `Y-m-d`, implied-decimal AIs (310n to 369n,
     * 390n to 395n) as exact decimal strings, others as the raw value.
     * Unmapped AIs are ignored. `$require` lists AIs every scan must carry.
     *
     * A scan that is not valid GS1, or misses a required AI, is rejected
     * before any state is written, like rejectWhen(), and before it. The same
     * rules run again on submit.
     *
     * @param  array<int|string, string>|Closure  $fill  AI => field, e.g. `['01' => 'gtin', '17' => 'expires_at']` (PHP turns keys such as '17' into ints; both work)
     * @param  list<int|string>|Closure  $require  AIs every scan must carry, e.g. `['01', '17', '10']`
     * @param  bool|Closure  $keepMissing  keep a mapped field's value when an accepted label lacks its AI (default: empty it)
     */
    public function gs1(array|Closure $fill = [], array|Closure $require = [], bool|Closure $keepMissing = false): static
    {
        $this->configureGs1($fill, $require, $keepMissing);
        $this->registerScanChecksRule();

        if ($this->hasRegisteredGs1Hook) {
            return $this;
        }

        $this->hasRegisteredGs1Hook = true;

        // Typed or pasted values: first, so later hooks see the bracketed string.
        array_unshift($this->afterStateUpdated, function (QrScanner $component, mixed $state, mixed $livewire): void {
            if ($component->isAcceptingScan || ! is_string($state) || blank($state)) {
                return;
            }

            $component->checkTypedGs1Value($state, $livewire);
        });

        return $this;
    }

    /**
     * A typed or pasted value goes through the same checks as a scan (GS1,
     * then validateUsing(), lookup and rejectWhen()), with source `manual`.
     * A rejection runs onRejected(), clears per clearOnReject() and shows
     * the reason under the field; an accepted value is normalized, fills
     * the mapped fields and runs onScanned().
     */
    protected function checkTypedGs1Value(string $state, mixed $livewire): void
    {
        $result = $this->runScanChecks($this->normalizeScannedCode($state), ScanInput::Manual);

        if (! $result->passes) {
            $this->rejectScan($result);

            if (is_object($livewire) && method_exists($livewire, 'dispatch')) {
                $livewire->dispatch('qr-scan-rejected', message: $result->message);
            }

            return;
        }

        if ($result->code !== $state) {
            $this->state($result->code);
        }

        $this->clearScanError();
        $this->fillGs1Fields(Gs1::parse($result->code), $this->getGs1ScanCheck());
        $this->completeAcceptedScan($result);
    }

    protected function acceptScan(ScanResult $result): void
    {
        $this->clearScanError();

        if ($this->isGs1()) {
            $this->fillGs1Fields(Gs1::parse($result->code), $this->getGs1ScanCheck());
        }

        $this->isAcceptingScan = true;

        try {
            $this->acceptScanThroughState($result);
        } finally {
            $this->isAcceptingScan = false;
        }
    }

    protected function rejectScan(ScanResult $result): void
    {
        $this->rejectScanThroughState($result);

        if ($this->reportsScanErrorsInErrorBag()) {
            $this->replaceScanError($result->message ?? (string) __('filament-qr-code::ui.scan_rejected'));
        }
    }

    /**
     * Single mode puts a rejection in the form's error bag under the field,
     * so server-rendered HTML shows it. Continuous mode keeps its status
     * line: the field is ready for the next scan, not invalid.
     */
    protected function reportsScanErrorsInErrorBag(): bool
    {
        return ! $this->isContinuous();
    }

    protected function replaceScanError(string $message): void
    {
        $livewire = $this->getLivewire();
        $statePath = $this->getStatePath();

        $livewire->resetErrorBag($statePath);
        $livewire->addError($statePath, $message);
    }

    protected function clearScanError(): void
    {
        if ($this->reportsScanErrorsInErrorBag()) {
            $this->getLivewire()->resetErrorBag($this->getStatePath());
        }
    }

    protected function prepareScanCode(string $code, ?ScanInput $source): ScanResult|string
    {
        // An empty scan keeps its own "empty" message from the pipeline.
        if (! $this->isGs1() || $code === '') {
            return $code;
        }

        $elements = $this->getGs1ScanCheck()->inspect($code);

        return $elements instanceof ElementString
            ? $elements->bracketed()
            : ScanResult::reject($code, $source, $elements);
    }

    protected function hasScanCodePreparation(): bool
    {
        return $this->isGs1();
    }

    /** Show the image-upload fallback in the camera modal. */
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
     * dispatched so the app can notify the operator. Camera and gun scans
     * are checked on the server first, so a rejected scan is never
     * announced as added; typed values are checked after the state update.
     */
    public function rejectWhen(Closure $predicate, string|Closure $message): static
    {
        // Camera and gun scans go through handleScan(), so the browser only
        // announces the outcome once the server has decided.
        $this->scanCheckClosures[] = function (string $code) use ($predicate, $message): ?string {
            $named = ['rawValue' => $code, 'state' => $code];

            if (! $this->evaluate($predicate, $named)) {
                return null;
            }

            return (string) $this->evaluate($message, $named);
        };

        $this->afterStateUpdated(function ($component, $state, $livewire) use ($predicate, $message): void {
            // A gs1() field already ran this check with the others on the typed value.
            if (blank($state) || $component->isGs1()) {
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

    /** Run the scanFormat() closure on a value (programmatic only; live scans use normalizeUsing()). */
    public function formatScannedValue(string $rawValue): mixed
    {
        if ($this->scanFormatter instanceof Closure) {
            return $this->evaluate($this->scanFormatter, ['rawValue' => $rawValue, 'state' => $rawValue]);
        }

        return $rawValue;
    }

    /** Run the onScan() closure and fire QrCodeScanned (programmatic only). */
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
