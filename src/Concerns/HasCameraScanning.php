<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Enums\ScannerPreset;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;
use Mmuqiitf\FilamentQrCode\Support\ScanMessages;

trait HasCameraScanning
{
    protected ScannerPreset|string|Closure|null $preset = null;

    protected bool $presetCustomized = false;

    protected int|Closure|null $fps = null;

    protected bool $fpsCustomized = false;

    protected int|Closure|null $qrbox = null;

    protected bool $qrboxCustomized = false;

    protected int|float|Closure|null $scanRegionWidth = null;

    protected int|float|Closure|null $scanRegionHeight = null;

    protected bool $scanRegionCustomized = false;

    protected bool|Closure|null $preferRearCamera = null;

    protected bool|Closure|null $cameraControls = null;

    /**
     * @var array<int, BarcodeFormat|string>|Closure
     */
    protected array|Closure $supportedFormats = [];

    protected bool $formatsCustomized = false;

    /**
     * Bundle camera defaults (`formats`, `fps`, `scanRegion`, `preferRearCamera`, `cameraControls`)
     * for a common workflow; explicit setters always win regardless of call order.
     */
    public function preset(ScannerPreset|string|Closure|null $preset): static
    {
        if (is_string($preset) && ScannerPreset::tryFrom($preset) === null) {
            throw new InvalidArgumentException("Unknown scanner preset [{$preset}].");
        }

        $this->preset = $preset;
        $this->presetCustomized = true;

        return $this;
    }

    public function getPreset(): ?ScannerPreset
    {
        $raw = $this->presetCustomized
            ? $this->evaluate($this->preset)
            : (function_exists('config') ? config('qr-code.camera.preset') : null);

        if ($raw === null || $raw === '') {
            return null;
        }

        if ($raw instanceof ScannerPreset) {
            return $raw;
        }

        if (is_string($raw)) {
            $resolved = ScannerPreset::tryFrom($raw);
            if ($resolved === null && $this->presetCustomized) {
                throw new InvalidArgumentException("Unknown scanner preset [{$raw}].");
            }

            return $resolved;
        }

        if ($this->presetCustomized) {
            throw new InvalidArgumentException('Scanner preset must be a ScannerPreset case, preset string, or null.');
        }

        return null;
    }

    /** Camera decode attempts per second (config `camera.fps`); setting it turns off the automatic slow-device degrade. */
    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;
        $this->fpsCustomized = true;

        return $this;
    }

    /** Maximum decode-box edge in pixels; the real box scales to the viewfinder (config `camera.qrbox`). */
    public function qrbox(int|Closure $qrbox): static
    {
        $this->qrbox = $qrbox;
        $this->qrboxCustomized = true;

        return $this;
    }

    /**
     * Explicit decode box and reticle dimensions (config `camera.scan_region`).
     * Values <= 1.0 are viewfinder ratios (e.g. 0.85, 0.35); integers > 1 are pixel maxima.
     * Pass `scanRegion()` or `scanRegion(null, null)` to clear a preset or config region.
     */
    public function scanRegion(int|float|Closure|null $width = null, int|float|Closure|null $height = null): static
    {
        if (! $width instanceof Closure && ! $height instanceof Closure) {
            $this->validateScanRegionValues($width, $height);
        }

        $this->scanRegionWidth = $width;
        $this->scanRegionHeight = $height;
        $this->scanRegionCustomized = true;

        return $this;
    }

    /**
     * @return array{width: int|float, height: int|float}|null
     */
    public function getScanRegion(): ?array
    {
        if ($this->scanRegionCustomized) {
            $width = $this->evaluate($this->scanRegionWidth);
            $height = $this->evaluate($this->scanRegionHeight);

            return $this->validateScanRegionValues($width, $height);
        }

        if ($this->qrboxCustomized) {
            return null;
        }

        $preset = $this->getPreset();
        if ($preset !== null) {
            return $preset->scanRegion();
        }

        return $this->readConfiguredScanRegion();
    }

    /** Pick the rear camera first when no choice is remembered (config `camera.prefer_rear_camera`). */
    public function preferRearCamera(bool|Closure $condition = true): static
    {
        $this->preferRearCamera = $condition;

        return $this;
    }

    /**
     * Torch and zoom controls appear when the running camera supports them;
     * pass false to keep the viewfinder free of them.
     */
    public function cameraControls(bool|Closure $condition = true): static
    {
        $this->cameraControls = $condition;

        return $this;
    }

    public function hasCameraControls(): bool
    {
        if ($this->cameraControls !== null) {
            return (bool) $this->evaluate($this->cameraControls);
        }

        $preset = $this->getPreset();
        if ($preset !== null) {
            return $preset->cameraControls();
        }

        return ConfigValue::bool('qr-code.camera.controls', true);
    }

    /**
     * Symbologies the camera decodes (BarcodeFormat cases or strings); filtering speeds decoding.
     *
     * @param  array<int, BarcodeFormat|string>|Closure  $formats
     */
    public function formats(array|Closure $formats): static
    {
        $this->supportedFormats = $formats;
        $this->formatsCustomized = true;

        return $this;
    }

    public function getFps(): int
    {
        if ($this->fps !== null) {
            return max(1, (int) $this->evaluate($this->fps));
        }

        $preset = $this->getPreset();
        if ($preset !== null) {
            return max(1, $preset->fps());
        }

        return ConfigValue::int('qr-code.camera.fps', 25, minimum: 1);
    }

    /**
     * Decode attempts per second actually sent to the camera decoder.
     * Unrestricted symbologies (empty formats) at the default 25fps cause
     * main-thread decode lag, so the effective rate auto-degrades to 12
     * unless the developer explicitly called fps().
     */
    public function getEffectiveFps(): int
    {
        $fps = $this->getFps();

        if (! $this->fpsCustomized && $this->getSupportedFormats() === [] && $fps >= 25) {
            return 12;
        }

        return max(1, $fps);
    }

    public function shouldWarnUnrestrictedPerformance(): bool
    {
        return ! $this->fpsCustomized && $this->getSupportedFormats() === [] && $this->getFps() >= 25;
    }

    public function getQrbox(): int
    {
        if ($this->qrbox === null) {
            return ConfigValue::int('qr-code.camera.qrbox', 250, minimum: 1);
        }

        return max(1, (int) $this->evaluate($this->qrbox));
    }

    public function isPreferRearCamera(): bool
    {
        if ($this->preferRearCamera !== null) {
            return (bool) $this->evaluate($this->preferRearCamera);
        }

        $preset = $this->getPreset();
        if ($preset !== null) {
            return $preset->preferRearCamera();
        }

        return ConfigValue::bool('qr-code.camera.prefer_rear_camera', true);
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedFormats(): array
    {
        if ($this->formatsCustomized) {
            $formats = $this->evaluate($this->supportedFormats);
        } else {
            $preset = $this->getPreset();
            $formats = $preset !== null ? $preset->formats() : [];
        }

        if (! is_array($formats)) {
            return [];
        }

        $result = [];
        foreach ($formats as $format) {
            if ($format instanceof BarcodeFormat) {
                $result[] = $format->value;
            } elseif (is_string($format)) {
                $result[] = $format;
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function getCameraScanPayload(): array
    {
        $payload = [
            'fps' => $this->getEffectiveFps(),
            'qrbox' => $this->getQrbox(),
            'preferRearCamera' => $this->isPreferRearCamera(),
            'cameraControls' => $this->hasCameraControls(),
            'formats' => $this->getSupportedFormats(),
            ...ScanMessages::forCamera(),
        ];

        $scanRegion = $this->getScanRegion();
        if ($scanRegion !== null) {
            $payload['scanRegion'] = $scanRegion;
        }

        return $payload;
    }

    /**
     * @return array{width: int|float, height: int|float}|null
     */
    private function validateScanRegionValues(mixed $width, mixed $height): ?array
    {
        if ($width === null && $height === null) {
            return null;
        }

        if ($width === null || $height === null) {
            throw new InvalidArgumentException('Both width and height must be provided when configuring scanRegion().');
        }

        return [
            'width' => $this->validateScanRegionAxis($width, 'width'),
            'height' => $this->validateScanRegionAxis($height, 'height'),
        ];
    }

    private function validateScanRegionAxis(mixed $value, string $axis): int|float
    {
        if (! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException("Scan region {$axis} must be a positive integer (px) or float ratio (<= 1.0).");
        }

        if (is_float($value) && (! is_finite($value) || $value > 1.0)) {
            throw new InvalidArgumentException("Scan region {$axis} ratio cannot exceed 1.0.");
        }

        if ($value <= 0) {
            throw new InvalidArgumentException("Scan region {$axis} must be greater than zero.");
        }

        return $value;
    }

    /**
     * @return array{width: int|float, height: int|float}|null
     */
    private function readConfiguredScanRegion(): ?array
    {
        if (! function_exists('config')) {
            return null;
        }

        $configured = config('qr-code.camera.scan_region');
        if (! is_array($configured)) {
            return null;
        }

        $width = $configured['width'] ?? $configured[0] ?? null;
        $height = $configured['height'] ?? $configured[1] ?? null;

        try {
            return $this->validateScanRegionValues($width, $height);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
