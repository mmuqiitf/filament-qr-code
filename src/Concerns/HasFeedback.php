<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;

trait HasFeedback
{
    protected bool|Closure|null $hasSound = null;

    protected bool|Closure|null $hasVibration = null;

    protected int|Closure|null $beepFrequencyHz = null;

    protected int|Closure|null $beepDurationMs = null;

    protected int|Closure|null $vibrateDurationMs = null;

    /** Play the synthesized beep on a successful scan (config `feedback.sound`). */
    public function sound(bool|Closure $condition = true): static
    {
        $this->hasSound = $condition;

        return $this;
    }

    /** @var list<int>|null */
    protected ?array $successVibrationPattern = null;

    /** @var list<int>|null */
    protected ?array $errorVibrationPattern = null;

    /**
     * Vibrate on a successful scan; success / error patterns apply to server-checked fields.
     *
     * @param  list<int>|null  $success
     * @param  list<int>|null  $error
     */
    public function vibrate(bool|Closure $condition = true, ?array $success = null, ?array $error = null): static
    {
        $this->hasVibration = $condition;
        $this->successVibrationPattern = $success ?? $this->successVibrationPattern;
        $this->errorVibrationPattern = $error ?? $this->errorVibrationPattern;

        return $this;
    }

    /** Beep pitch in hertz (config `feedback.beep_frequency`). */
    public function beepFrequency(int|Closure $hertz): static
    {
        $this->beepFrequencyHz = $hertz;

        return $this;
    }

    /** Beep length in milliseconds (config `feedback.beep_duration_ms`). */
    public function beepDuration(int|Closure $milliseconds): static
    {
        $this->beepDurationMs = $milliseconds;

        return $this;
    }

    /** Haptic pulse length in milliseconds (config `feedback.vibrate_duration_ms`). */
    public function vibrateDuration(int|Closure $milliseconds): static
    {
        $this->vibrateDurationMs = $milliseconds;

        return $this;
    }

    public function hasSound(): bool
    {
        if ($this->hasSound === null) {
            return ConfigValue::bool('qr-code.feedback.sound', true);
        }

        return (bool) $this->evaluate($this->hasSound);
    }

    public function hasVibration(): bool
    {
        if ($this->hasVibration === null) {
            return ConfigValue::bool('qr-code.feedback.vibrate', true);
        }

        return (bool) $this->evaluate($this->hasVibration);
    }

    public function getBeepFrequencyHz(): int
    {
        $value = $this->beepFrequencyHz === null ? null : $this->evaluate($this->beepFrequencyHz);

        if ($value === null) {
            return ConfigValue::int('qr-code.feedback.beep_frequency', 880, minimum: 100);
        }

        return max(100, (int) $value);
    }

    public function getBeepDurationMs(): int
    {
        $value = $this->beepDurationMs === null ? null : $this->evaluate($this->beepDurationMs);

        if ($value === null) {
            return ConfigValue::int('qr-code.feedback.beep_duration_ms', 80, minimum: 20);
        }

        return max(20, (int) $value);
    }

    public function getVibrateDurationMs(): int
    {
        $value = $this->vibrateDurationMs === null ? null : $this->evaluate($this->vibrateDurationMs);

        if ($value === null) {
            return ConfigValue::int('qr-code.feedback.vibrate_duration_ms', 100, minimum: 0);
        }

        return max(0, (int) $value);
    }

    /** @return array<string, mixed> */
    public function getFeedbackScanPayload(): array
    {
        return [
            'sound' => $this->hasSound(),
            'vibrate' => $this->hasVibration(),
            'beepFrequency' => $this->getBeepFrequencyHz(),
            'beepDurationMs' => $this->getBeepDurationMs(),
            'vibrateDurationMs' => $this->getVibrateDurationMs(),
        ];
    }
}
