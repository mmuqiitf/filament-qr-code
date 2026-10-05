<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;

class QrCollectAction extends Action
{
    use HasFeedback;
    use HasHardwareScanner;

    protected bool|Closure $allowDuplicates = false;

    protected int|Closure $fps = 25;

    protected int|Closure $qrbox = 250;

    /**
     * @var array<int, BarcodeFormat|string>|Closure
     */
    protected array|Closure $supportedFormats = [];

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
                'fps' => $this->getFps(),
                'qrbox' => $this->getQrbox(),
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
     * @param  array<int, BarcodeFormat|string>|Closure  $formats
     */
    public function formats(array|Closure $formats): static
    {
        $this->supportedFormats = $formats;

        return $this;
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

    public function getFps(): int
    {
        return (int) $this->evaluate($this->fps);
    }

    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;

        return $this;
    }

    public function getQrbox(): int
    {
        return (int) $this->evaluate($this->qrbox);
    }

    public function qrbox(int|Closure $qrbox): static
    {
        $this->qrbox = $qrbox;

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
