<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;

trait HasHardwareScanner
{
    protected bool|Closure $isHardwareScannerEnabled = true;

    protected int|Closure $burstThresholdMs = 50;

    protected bool|Closure $preventFormSubmit = true;

    public function hardwareScanner(
        bool|Closure $enabled = true,
        int|Closure $burstThresholdMs = 50,
        bool|Closure $preventFormSubmit = true
    ): static {
        $this->isHardwareScannerEnabled = $enabled;
        $this->burstThresholdMs = $burstThresholdMs;
        $this->preventFormSubmit = $preventFormSubmit;

        return $this;
    }

    public function isHardwareScannerEnabled(): bool
    {
        return (bool) $this->evaluate($this->isHardwareScannerEnabled);
    }

    public function getBurstThresholdMs(): int
    {
        return (int) $this->evaluate($this->burstThresholdMs);
    }

    public function shouldPreventFormSubmit(): bool
    {
        return (bool) $this->evaluate($this->preventFormSubmit);
    }
}
