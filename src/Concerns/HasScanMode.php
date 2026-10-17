<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Illuminate\Support\Facades\Log;
use Mmuqiitf\FilamentQrCode\Enums\ContinuousDisplay;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;

trait HasScanMode
{
    protected ScanMode|Closure $scanMode = ScanMode::Single;

    protected ContinuousDisplay|Closure|null $continuousDisplay = null;

    protected bool|Closure|null $pauseOnReject = null;

    protected bool|Closure|null $keepCameraOpen = null;

    /** @var array<string, true> */
    protected static array $warnedRequiredContinuousFields = [];

    public function mode(ScanMode|Closure $mode): static
    {
        $this->scanMode = $mode;

        return $this;
    }

    public function getMode(): ScanMode
    {
        $mode = $this->evaluate($this->scanMode);

        return $mode instanceof ScanMode ? $mode : ScanMode::Single;
    }

    public function isContinuous(): bool
    {
        return $this->getMode() === ScanMode::Continuous;
    }

    public function continuousDisplay(ContinuousDisplay|Closure $display): static
    {
        $this->continuousDisplay = $display;

        return $this;
    }

    public function getContinuousDisplay(): ContinuousDisplay
    {
        if ($this->continuousDisplay === null) {
            $configured = function_exists('config') ? config('qr-code.scan.continuous.display', 'empty') : 'empty';

            return is_string($configured)
                ? (ContinuousDisplay::tryFrom($configured) ?? ContinuousDisplay::Empty)
                : ContinuousDisplay::Empty;
        }

        $display = $this->evaluate($this->continuousDisplay);

        return $display instanceof ContinuousDisplay ? $display : ContinuousDisplay::Empty;
    }

    public function pauseOnReject(bool|Closure $condition = true): static
    {
        $this->pauseOnReject = $condition;

        return $this;
    }

    public function shouldPauseOnReject(): bool
    {
        if ($this->pauseOnReject === null) {
            return ConfigValue::bool('qr-code.scan.continuous.pause_on_reject', false);
        }

        return (bool) $this->evaluate($this->pauseOnReject);
    }

    public function keepCameraOpen(bool|Closure $condition = true): static
    {
        $this->keepCameraOpen = $condition;

        return $this;
    }

    public function shouldKeepCameraOpen(): bool
    {
        if ($this->keepCameraOpen === null) {
            return ConfigValue::bool('qr-code.scan.continuous.keep_camera_open', false);
        }

        return (bool) $this->evaluate($this->keepCameraOpen);
    }

    protected function getContinuousCooldownMs(): int
    {
        return ConfigValue::int('qr-code.scan.continuous.cooldown_ms', 300, minimum: 0);
    }

    protected function shouldEmptyStateAfterAccept(): bool
    {
        return $this->isContinuous() && $this->getContinuousDisplay() === ContinuousDisplay::Empty;
    }

    /** @return array{mode: string, continuousDisplay: string, pauseOnReject: bool, keepCameraOpen: bool} */
    public function getScanModeScanPayload(): array
    {
        $this->warnAboutRequiredContinuousField();

        return [
            'mode' => $this->getMode()->value,
            'continuousDisplay' => $this->getContinuousDisplay()->value,
            'pauseOnReject' => $this->shouldPauseOnReject(),
            'keepCameraOpen' => $this->shouldKeepCameraOpen(),
        ];
    }

    /**
     * A continuous field is usually blank at submit, so required() would always fail.
     * Warn once per field in local development instead of throwing.
     */
    protected function warnAboutRequiredContinuousField(): void
    {
        if (! $this->isContinuous() || ! $this->isRequired() || ! app()->isLocal()) {
            return;
        }

        $name = static::class.':'.$this->getName();

        if (isset(static::$warnedRequiredContinuousFields[$name])) {
            return;
        }

        static::$warnedRequiredContinuousFields[$name] = true;

        Log::warning("QrScanner [{$this->getName()}] is in continuous mode and marked required(); the field is usually empty at submit, so the form will not validate. Remove required() or use ->dehydrated(false).");
    }
}
