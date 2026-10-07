<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;

trait HasCameraScanning
{
    protected int|Closure $fps = 25;

    protected bool $fpsCustomized = false;

    protected int|Closure $qrbox = 250;

    protected bool|Closure $preferRearCamera = true;

    /**
     * @var array<int, BarcodeFormat|string>|Closure
     */
    protected array|Closure $supportedFormats = [];

    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;
        $this->fpsCustomized = true;

        return $this;
    }

    public function qrbox(int|Closure $qrbox): static
    {
        $this->qrbox = $qrbox;

        return $this;
    }

    public function preferRearCamera(bool|Closure $condition = true): static
    {
        $this->preferRearCamera = $condition;

        return $this;
    }

    /**
     * @param  array<int, BarcodeFormat|string>|Closure  $formats
     */
    public function formats(array|Closure $formats): static
    {
        $this->supportedFormats = $formats;

        return $this;
    }

    public function getFps(): int
    {
        return (int) $this->evaluate($this->fps);
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
        return (int) $this->evaluate($this->qrbox);
    }

    public function isPreferRearCamera(): bool
    {
        return (bool) $this->evaluate($this->preferRearCamera);
    }

    /**
     * @return array<int, string>
     */
    public function getSupportedFormats(): array
    {
        $formats = $this->evaluate($this->supportedFormats);
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
}
