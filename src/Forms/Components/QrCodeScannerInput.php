<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Mmuqiitf\FilamentQrCode\Enums\CameraFacing;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;

class QrCodeScannerInput extends TextInput
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-qr-code::forms.components.qr-code-scanner-input';

    protected CameraFacing|Closure $cameraFacing = CameraFacing::Auto;

    protected ScanMode|Closure $scanMode = ScanMode::Single;

    protected int|Closure $scanDelay = 1500;

    protected int|Closure $fps = 30;

    protected int|Closure $qrboxSize = 250;

    protected bool|Closure $showPreview = true;

    protected ?Closure $onScanCallback = null;

    protected ?Closure $onErrorCallback = null;

    protected $scannerModalWidth = '2xl';

    protected $slideOver = false;

    protected bool|Closure $beepOnScan = true;

    protected bool|Closure $vibrateOnScan = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Apply defaults from config
        $this->cameraFacing = CameraFacing::tryFrom(config('qr-code.camera_facing', 'environment')) ?? CameraFacing::Back;
        $this->scanMode = ScanMode::tryFrom(config('qr-code.scan_mode', 'single')) ?? ScanMode::Single;
        $this->scanDelay = config('qr-code.scan_delay', 1500);
        $this->fps = config('qr-code.fps', 30);
        $this->qrboxSize = config('qr-code.qrbox_size', 250);
        $this->showPreview = config('qr-code.show_preview', true);
        $this->scannerModalWidth = config('qr-code.modal_width', '2xl');
        $this->beepOnScan = config('qr-code.beep_on_scan', true);
        $this->vibrateOnScan = config('qr-code.vibrate_on_scan', true);

        // Defer suffix action setup until component is configured
        $this->suffixAction(
            fn (): Action => $this->getScannerAction()
        );
    }

    protected function getScannerAction(): Action
    {
        return Action::make('scan')
            ->icon('heroicon-o-qr-code')
            ->iconButton()
            ->tooltip(__('filament-qr-code::messages.scan_qr_code'))
            ->modalHeading(__('filament-qr-code::messages.scan_qr_code'))
            ->modalWidth(fn () => $this->evaluate($this->scannerModalWidth))
            ->slideOver(fn () => $this->evaluate($this->slideOver))
            ->modalContent(function () {
                /** @var view-string $view */
                $view = 'filament-qr-code::components.qr-scanner-modal';

                return view($view, [
                    'statePath' => $this->getStatePath(),
                    'cameraFacing' => $this->evaluate($this->cameraFacing),
                    'scanMode' => $this->evaluate($this->scanMode),
                    'scanDelay' => $this->evaluate($this->scanDelay),
                    'fps' => $this->evaluate($this->fps),
                    'qrboxSize' => $this->evaluate($this->qrboxSize),
                    'showPreview' => $this->evaluate($this->showPreview),
                    'beepOnScan' => $this->evaluate($this->beepOnScan),
                    'vibrateOnScan' => $this->evaluate($this->vibrateOnScan),
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament-qr-code::messages.close'))
            ->extraModalWindowAttributes([
                'x-on:qr-scanned.window' => new HtmlString(
                    "if (\$event.detail.statePath === '{$this->getStatePath()}') { ".
                    "\$wire.set('{$this->getStatePath()}', \$event.detail.value); ".
                    'close(); }'
                ),
            ]);
    }

    // Fluent configuration methods with Closure support
    public function cameraFacing(CameraFacing|Closure $facing): static
    {
        $this->cameraFacing = $facing;

        return $this;
    }

    public function useBackCamera(): static
    {
        return $this->cameraFacing(CameraFacing::Back);
    }

    public function useFrontCamera(): static
    {
        return $this->cameraFacing(CameraFacing::Front);
    }

    public function scanMode(ScanMode|Closure $mode): static
    {
        $this->scanMode = $mode;

        return $this;
    }

    public function continuous(): static
    {
        return $this->scanMode(ScanMode::Continuous);
    }

    public function sequence(): static
    {
        return $this->scanMode(ScanMode::Sequence);
    }

    public function scanDelay(int|Closure $milliseconds): static
    {
        $this->scanDelay = $milliseconds;

        return $this;
    }

    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;

        return $this;
    }

    public function qrboxSize(int|Closure $size): static
    {
        $this->qrboxSize = $size;

        return $this;
    }

    public function showPreview(bool|Closure $show = true): static
    {
        $this->showPreview = $show;

        return $this;
    }

    public function hidePreview(): static
    {
        return $this->showPreview(false);
    }

    public function modalWidth(Width|string|callable|null $width = null): static
    {
        $this->scannerModalWidth = $width;

        return $this;
    }

    public function slideOver(bool|Closure $slideOver = true): static
    {
        $this->slideOver = $slideOver;

        return $this;
    }

    public function beepOnScan(bool|Closure $beep = true): static
    {
        $this->beepOnScan = $beep;

        return $this;
    }

    public function vibrateOnScan(bool|Closure $vibrate = true): static
    {
        $this->vibrateOnScan = $vibrate;

        return $this;
    }

    public function onScan(Closure $callback): static
    {
        $this->onScanCallback = $callback;

        return $this;
    }

    public function onError(Closure $callback): static
    {
        $this->onErrorCallback = $callback;

        return $this;
    }

    // Getters for the view
    public function getCameraFacing(): CameraFacing
    {
        return $this->evaluate($this->cameraFacing);
    }

    public function getScanMode(): ScanMode
    {
        return $this->evaluate($this->scanMode);
    }

    public function getScanDelay(): int
    {
        return $this->evaluate($this->scanDelay);
    }

    public function getFps(): int
    {
        return $this->evaluate($this->fps);
    }

    public function getQrboxSize(): int
    {
        return $this->evaluate($this->qrboxSize);
    }

    public function getShowPreview(): bool
    {
        return $this->evaluate($this->showPreview);
    }

    public function getBeepOnScan(): bool
    {
        return $this->evaluate($this->beepOnScan);
    }

    public function getVibrateOnScan(): bool
    {
        return $this->evaluate($this->vibrateOnScan);
    }
}
