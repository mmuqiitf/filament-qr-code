<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;

trait HasFeedback
{
    protected bool|Closure|null $hasSound = null;

    protected bool|Closure|null $hasVibration = null;

    protected int|Closure|null $beepFrequencyHz = null;

    protected int|Closure|null $beepDurationMs = null;

    protected int|Closure|null $vibrateDurationMs = null;

    public function sound(bool|Closure $condition = true): static
    {
        $this->hasSound = $condition;

        return $this;
    }

    public function vibrate(bool|Closure $condition = true): static
    {
        $this->hasVibration = $condition;

        return $this;
    }

    public function beepFrequency(int|Closure $hertz): static
    {
        $this->beepFrequencyHz = $hertz;

        return $this;
    }

    public function beepDuration(int|Closure $milliseconds): static
    {
        $this->beepDurationMs = $milliseconds;

        return $this;
    }

    public function vibrateDuration(int|Closure $milliseconds): static
    {
        $this->vibrateDurationMs = $milliseconds;

        return $this;
    }

    public function hasSound(): bool
    {
        if ($this->hasSound === null) {
            $configured = function_exists('config') ? config('qr-code.feedback.sound', true) : true;

            return is_bool($configured) ? $configured : (bool) $configured;
        }

        return (bool) $this->evaluate($this->hasSound);
    }

    public function hasVibration(): bool
    {
        if ($this->hasVibration === null) {
            $configured = function_exists('config') ? config('qr-code.feedback.vibrate', true) : true;

            return is_bool($configured) ? $configured : (bool) $configured;
        }

        return (bool) $this->evaluate($this->hasVibration);
    }

    public function getBeepFrequencyHz(): int
    {
        $value = $this->beepFrequencyHz === null ? null : $this->evaluate($this->beepFrequencyHz);

        if ($value === null) {
            $configured = function_exists('config') ? config('qr-code.feedback.beep_frequency', 880) : 880;

            return is_numeric($configured) ? max(100, (int) $configured) : 880;
        }

        return max(100, (int) $value);
    }

    public function getBeepDurationMs(): int
    {
        $value = $this->beepDurationMs === null ? null : $this->evaluate($this->beepDurationMs);

        if ($value === null) {
            $configured = function_exists('config') ? config('qr-code.feedback.beep_duration_ms', 80) : 80;

            return is_numeric($configured) ? max(20, (int) $configured) : 80;
        }

        return max(20, (int) $value);
    }

    public function getVibrateDurationMs(): int
    {
        $value = $this->vibrateDurationMs === null ? null : $this->evaluate($this->vibrateDurationMs);

        if ($value === null) {
            $configured = function_exists('config') ? config('qr-code.feedback.vibrate_duration_ms', 100) : 100;

            return is_numeric($configured) ? max(0, (int) $configured) : 100;
        }

        return max(0, (int) $value);
    }
}
