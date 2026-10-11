<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;

class QrCollectAction extends Action
{
    use HasCameraScanning;
    use HasFeedback;
    use HasHardwareScanner;
    use HasScanPayload;

    protected bool|Closure $allowDuplicates = false;

    protected ?Closure $onItemScanned = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('qr_collect');
        $this->label(__('filament-qr-code::ui.batch_scan_qrcodes'));
        $this->icon('heroicon-o-qr-code');
        $this->color('primary');

        $this->modalHeading(__('filament-qr-code::ui.batch_qr_scanner'));
        $this->modalDescription(__('filament-qr-code::ui.collect_description'));
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel(__('filament-qr-code::ui.done'));

        $this->modalContent(function (): View {
            /** @var view-string $viewName */
            $viewName = 'filament-qr-code::components.qr-collector-modal';

            return view($viewName, [
                'actionName' => $this->getName(),
                'scanPayload' => $this->getScanPayload(),
                'allowDuplicates' => $this->isDuplicatesAllowed(),
                'sound' => $this->hasSound(),
                'vibrate' => $this->hasVibration(),
                'beepFrequency' => $this->getBeepFrequencyHz(),
                'beepDuration' => $this->getBeepDurationMs(),
                'vibrateDuration' => $this->getVibrateDurationMs(),
                'hardwareScanner' => $this->isHardwareScannerEnabled(),
                'burstThresholdMs' => $this->getBurstThresholdMs(),
                'terminators' => $this->getTerminators(),
                'minBarcodeLength' => $this->getMinBarcodeLength(),
                'scanTimeoutMs' => $this->getScanTimeoutMs(),
                'suppressWhenGlobal' => $this->isSuppressedWhenGlobalListenerActive(),
                'insecureMessage' => __('filament-qr-code::ui.camera_needs_secure_context'),
                'fps' => $this->getEffectiveFps(),
                'qrbox' => $this->getQrbox(),
                'preferRearCamera' => $this->isPreferRearCamera(),
                'formats' => $this->getSupportedFormats(),
            ]);
        });
    }

    public function allowDuplicates(bool|Closure $condition = true): static
    {
        $this->allowDuplicates = $condition;

        return $this;
    }

    public function onScan(?Closure $callback): static
    {
        $this->onItemScanned = $callback;

        return $this;
    }

    /**
     * Invoke the registered onScan callback for a collected code.
     *
     * The action modal runs in the browser, so host Livewire components
     * should forward each `qr-collector-item-added` window event (or call
     * `$wire.handleCollectorScan`, when defined) into this method to run
     * server-side post-processing for the scanned code.
     */
    public function handleScan(string $code, mixed $livewire = null): mixed
    {
        event(new QrCodeScanned(
            code: $code,
            source: 'collect-action',
            context: ['livewire' => is_object($livewire) ? $livewire::class : null],
        ));

        if ($this->onItemScanned === null) {
            return null;
        }

        return $this->evaluate($this->onItemScanned, [
            'code' => $code,
            'livewire' => $livewire,
            'action' => $this,
        ]);
    }

    public function isDuplicatesAllowed(): bool
    {
        return (bool) $this->evaluate($this->allowDuplicates);
    }
}
