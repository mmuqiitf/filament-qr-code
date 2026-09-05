<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;

trait HasFeedback
{
    protected bool|Closure $hasSound = true;

    protected bool|Closure $hasVibration = true;

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

    public function hasSound(): bool
    {
        return (bool) $this->evaluate($this->hasSound);
    }

    public function hasVibration(): bool
    {
        return (bool) $this->evaluate($this->hasVibration);
    }
}
