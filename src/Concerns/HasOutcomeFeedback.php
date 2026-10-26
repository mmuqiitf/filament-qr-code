<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;

trait HasOutcomeFeedback
{
    protected bool|Closure|null $successBeep = null;

    protected bool|Closure|null $errorBeep = null;

    protected bool|Closure|null $hasFlash = null;

    /** Beep on accepted / rejected scans of server-checked fields; null keeps the config default. */
    public function beep(bool|Closure|null $success = null, bool|Closure|null $error = null): static
    {
        $this->successBeep = $success ?? $this->successBeep;
        $this->errorBeep = $error ?? $this->errorBeep;

        return $this;
    }

    /** Flash the field green / red on accepted / rejected scans of server-checked fields. */
    public function flash(bool|Closure $condition = true): static
    {
        $this->hasFlash = $condition;

        return $this;
    }

    /** @return array{sound: bool, vibrate: list<int>, flash: bool} */
    public function getSuccessFeedback(): array
    {
        return $this->resolveOutcomeFeedback('success', $this->successBeep, $this->successVibrationPattern, [100]);
    }

    /** @return array{sound: bool, vibrate: list<int>, flash: bool} */
    public function getErrorFeedback(): array
    {
        return $this->resolveOutcomeFeedback('error', $this->errorBeep, $this->errorVibrationPattern, [80, 60, 80]);
    }

    /** @return array{successFeedback: array{sound: bool, vibrate: list<int>, flash: bool}, errorFeedback: array{sound: bool, vibrate: list<int>, flash: bool}} */
    public function getOutcomeFeedbackScanPayload(): array
    {
        return [
            'successFeedback' => $this->getSuccessFeedback(),
            'errorFeedback' => $this->getErrorFeedback(),
        ];
    }

    /**
     * @param  list<int>|null  $pattern
     * @param  list<int>  $defaultPattern
     * @return array{sound: bool, vibrate: list<int>, flash: bool}
     */
    protected function resolveOutcomeFeedback(string $outcome, bool|Closure|null $beep, ?array $pattern, array $defaultPattern): array
    {
        $sound = $beep === null
            ? ConfigValue::bool("qr-code.feedback.{$outcome}.sound", true)
            : (bool) $this->evaluate($beep);

        $flash = $this->hasFlash === null
            ? ConfigValue::bool("qr-code.feedback.{$outcome}.flash", true)
            : (bool) $this->evaluate($this->hasFlash);

        return [
            'sound' => $this->hasSound() && $sound,
            'vibrate' => $this->hasVibration()
                ? ($pattern ?? ConfigValue::intList("qr-code.feedback.{$outcome}.vibrate", $defaultPattern))
                : [],
            'flash' => $flash,
        ];
    }
}
