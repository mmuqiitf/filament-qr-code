<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;

trait HasHardwareScanner
{
    protected bool|Closure|null $isHardwareScannerEnabled = null;

    protected int|Closure|null $burstThresholdMs = null;

    protected bool|Closure|null $preventFormSubmit = null;

    /**
     * @var array<int, string>|Closure|null
     */
    protected array|Closure|null $terminators = null;

    protected int|Closure|null $minBarcodeLength = null;

    protected int|Closure|null $scanTimeoutMs = null;

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
        if ($this->isHardwareScannerEnabled === null) {
            $configured = function_exists('config') ? config('qr-code.hardware_scanner.enabled', true) : true;

            return is_bool($configured) ? $configured : (bool) $configured;
        }

        return (bool) $this->evaluate($this->isHardwareScannerEnabled);
    }

    public function getBurstThresholdMs(): int
    {
        if ($this->burstThresholdMs === null) {
            $configured = function_exists('config') ? config('qr-code.hardware_scanner.burst_threshold_ms', 50) : 50;

            return is_numeric($configured) ? max(1, (int) $configured) : 50;
        }

        return max(1, (int) $this->evaluate($this->burstThresholdMs));
    }

    public function shouldPreventFormSubmit(): bool
    {
        if ($this->preventFormSubmit === null) {
            $configured = function_exists('config') ? config('qr-code.hardware_scanner.prevent_form_submit', true) : true;

            return is_bool($configured) ? $configured : (bool) $configured;
        }

        return (bool) $this->evaluate($this->preventFormSubmit);
    }

    /**
     * @return array<int, string>
     */
    public function getTerminators(): array
    {
        $terminators = $this->terminators === null
            ? (function_exists('config') ? config('qr-code.hardware_scanner.default_terminators', ['Enter', 'Tab']) : ['Enter', 'Tab'])
            : $this->evaluate($this->terminators);

        if (! is_array($terminators)) {
            return ['Enter', 'Tab'];
        }

        return array_values(array_filter($terminators, fn ($terminator) => is_string($terminator) && $terminator !== ''));
    }

    public function getMinBarcodeLength(): int
    {
        if ($this->minBarcodeLength === null) {
            $configured = function_exists('config') ? config('qr-code.hardware_scanner.min_barcode_length', 2) : 2;

            return max(1, is_numeric($configured) ? (int) $configured : 2);
        }

        return max(1, (int) $this->evaluate($this->minBarcodeLength));
    }

    public function scanTimeout(int|Closure $milliseconds): static
    {
        $this->scanTimeoutMs = $milliseconds;

        return $this;
    }

    public function getScanTimeoutMs(): int
    {
        if ($this->scanTimeoutMs === null) {
            $configured = function_exists('config') ? config('qr-code.hardware_scanner.scan_timeout_ms', 150) : 150;

            return max(0, is_numeric($configured) ? (int) $configured : 150);
        }

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
