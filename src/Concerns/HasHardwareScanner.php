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

    protected int|Closure $scanTimeoutMs = 150;

    protected bool|Closure $suppressWhenGlobalListenerActive = true;

    /**
     * @param  array<int, string>|Closure  $terminators
     */
    public function hardwareScanner(
        bool|Closure $enabled = true,
        int|Closure $burstThresholdMs = 50,
        bool|Closure $preventFormSubmit = true,
        array|Closure $terminators = ['Enter', 'Tab'],
        int|Closure $minBarcodeLength = 2,
        int|Closure $scanTimeoutMs = 150,
        bool|Closure $suppressWhenGlobalListenerActive = true,
    ): static {
        $this->isHardwareScannerEnabled = $enabled;
        $this->burstThresholdMs = $burstThresholdMs;
        $this->preventFormSubmit = $preventFormSubmit;
        $this->terminators = $terminators;
        $this->minBarcodeLength = $minBarcodeLength;
        $this->scanTimeoutMs = $scanTimeoutMs;
        $this->suppressWhenGlobalListenerActive = $suppressWhenGlobalListenerActive;

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

    public function scanTimeout(int|Closure $milliseconds): static
    {
        $this->scanTimeoutMs = $milliseconds;

        return $this;
    }

    public function getScanTimeoutMs(): int
    {
        return max(0, (int) $this->evaluate($this->scanTimeoutMs));
    }

    /**
     * When a page-global QrHardwareScannerListener is mounted, field-scoped
     * burst handlers stand down by default so the same gun burst is not
     * handled twice. Pass false to force the field listener to stay active.
     */
    public function suppressWhenGlobalListener(bool|Closure $condition = true): static
    {
        $this->suppressWhenGlobalListenerActive = $condition;

        return $this;
    }

    public function isSuppressedWhenGlobalListenerActive(): bool
    {
        return (bool) $this->evaluate($this->suppressWhenGlobalListenerActive);
    }

    /**
     * Strip handheld-scanner framing (STX/ETX prefixes, CR/LF suffixes) and
     * surrounding whitespace. Mirrors the JS sanitizeScannedValue().
     */
    public static function sanitizeScannedValue(string $value): string
    {
        return trim($value, "\x00..\x1F\x7F \t\n\r\0\x0B");
    }
}
