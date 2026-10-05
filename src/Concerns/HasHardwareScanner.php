<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;

trait HasHardwareScanner
{
    protected bool|Closure $isHardwareScannerEnabled = true;

    protected int|Closure $burstThresholdMs = 50;

    protected bool|Closure $preventFormSubmit = true;

    /**
     * @var array<int, string>|Closure
     */
    protected array|Closure $terminators = ['Enter', 'Tab'];

    protected int|Closure $minBarcodeLength = 2;

    /**
     * @param  array<int, string>|Closure  $terminators
     */
    public function hardwareScanner(
        bool|Closure $enabled = true,
        int|Closure $burstThresholdMs = 50,
        bool|Closure $preventFormSubmit = true,
        array|Closure $terminators = ['Enter', 'Tab'],
        int|Closure $minBarcodeLength = 2,
    ): static {
        $this->isHardwareScannerEnabled = $enabled;
        $this->burstThresholdMs = $burstThresholdMs;
        $this->preventFormSubmit = $preventFormSubmit;
        $this->terminators = $terminators;
        $this->minBarcodeLength = $minBarcodeLength;

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

    /**
     * @return array<int, string>
     */
    public function getTerminators(): array
    {
        $terminators = $this->evaluate($this->terminators);

        if (! is_array($terminators)) {
            return ['Enter', 'Tab'];
        }

        return array_values(array_filter($terminators, fn ($terminator) => is_string($terminator) && $terminator !== ''));
    }

    public function getMinBarcodeLength(): int
    {
        return max(1, (int) $this->evaluate($this->minBarcodeLength));
    }
}
