<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;

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

    protected int|Closure|null $minFastKeys = null;

    protected int|Closure|null $scanTimeoutMs = null;

    /**
     * Tune the keyboard-wedge interceptor in one call (config `hardware_scanner.*`).
     *
     * @param  array<int, string>|Closure  $terminators
     */
    public function hardwareScanner(
        bool|Closure $enabled = true,
        int|Closure $burstThresholdMs = 50,
        bool|Closure $preventFormSubmit = true,
        array|Closure $terminators = ['Enter', 'Tab'],
        int|Closure $minBarcodeLength = 2,
        int|Closure $scanTimeoutMs = 150,
        int|Closure|null $minFastKeys = null,
    ): static {
        $this->isHardwareScannerEnabled = $enabled;
        $this->burstThresholdMs = $burstThresholdMs;
        $this->preventFormSubmit = $preventFormSubmit;
        $this->terminators = $terminators;
        $this->minBarcodeLength = $minBarcodeLength;
        $this->scanTimeoutMs = $scanTimeoutMs;
        $this->minFastKeys = $minFastKeys;

        return $this;
    }

    /** Consecutive fast keystroke gaps required before a terminator counts as a scan (config `hardware_scanner.min_fast_keys`). */
    public function minFastKeys(int|Closure $count): static
    {
        $this->minFastKeys = $count;

        return $this;
    }

    public function getMinFastKeys(): int
    {
        if ($this->minFastKeys === null) {
            return ConfigValue::int('qr-code.hardware_scanner.min_fast_keys', 3, minimum: 1);
        }

        return max(1, (int) $this->evaluate($this->minFastKeys));
    }

    public function isHardwareScannerEnabled(): bool
    {
        if ($this->isHardwareScannerEnabled === null) {
            return ConfigValue::bool('qr-code.hardware_scanner.enabled', true);
        }

        return (bool) $this->evaluate($this->isHardwareScannerEnabled);
    }

    public function getBurstThresholdMs(): int
    {
        if ($this->burstThresholdMs === null) {
            return ConfigValue::int('qr-code.hardware_scanner.burst_threshold_ms', 50, minimum: 1);
        }

        return max(1, (int) $this->evaluate($this->burstThresholdMs));
    }

    public function shouldPreventFormSubmit(): bool
    {
        if ($this->preventFormSubmit === null) {
            return ConfigValue::bool('qr-code.hardware_scanner.prevent_form_submit', true);
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
            return ConfigValue::int('qr-code.hardware_scanner.min_barcode_length', 2, minimum: 1);
        }

        return max(1, (int) $this->evaluate($this->minBarcodeLength));
    }

    /** Flush window in milliseconds for guns that send no terminator; 0 disables (config `hardware_scanner.scan_timeout_ms`). */
    public function scanTimeout(int|Closure $milliseconds): static
    {
        $this->scanTimeoutMs = $milliseconds;

        return $this;
    }

    public function getScanTimeoutMs(): int
    {
        if ($this->scanTimeoutMs === null) {
            return ConfigValue::int('qr-code.hardware_scanner.scan_timeout_ms', 150, minimum: 0);
        }

        return max(0, (int) $this->evaluate($this->scanTimeoutMs));
    }

    /** @return array<string, mixed> */
    public function getHardwareScannerScanPayload(): array
    {
        return [
            'hardwareScanner' => $this->isHardwareScannerEnabled(),
            'burstThresholdMs' => $this->getBurstThresholdMs(),
            'terminators' => $this->getTerminators(),
            'minBarcodeLength' => $this->getMinBarcodeLength(),
            'minFastKeys' => $this->getMinFastKeys(),
            'scanTimeoutMs' => $this->getScanTimeoutMs(),
            'preventSubmit' => $this->shouldPreventFormSubmit(),
        ];
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
