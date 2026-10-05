<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;

class QrScanSequence extends Component
{
    use HasFeedback;
    use HasHardwareScanner;

    protected string $view = 'filament-qr-code::components.qr-scan-sequence';

    /**
     * @var array<int, array{key: string, label: string}|string>|Closure
     */
    protected array|Closure $scanFields = [];

    protected int|Closure $fps = 25;

    protected int|Closure $qrbox = 250;

    protected bool|Closure $preferRearCamera = true;

    /**
     * @var array<int, BarcodeFormat|string>|Closure
     */
    protected array|Closure $supportedFormats = [];

    protected string|Closure $statePathPrefix = 'data';

    protected ?Closure $scanFormatter = null;

    protected ?Closure $onStepCallback = null;

    /**
     * @param  array<int, array{key: string, label: string}|string>|Closure  $fields
     */
    public static function make(array|Closure $fields = []): static
    {
        $static = app(static::class);
        $static->fields($fields);

        return $static;
    }

    /**
     * @param  array<int, array{key: string, label: string}|string>|Closure  $fields
     */
    public function fields(array|Closure $fields): static
    {
        $this->scanFields = $fields;

        return $this;
    }

    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;

        return $this;
    }

    public function qrbox(int|Closure $qrbox): static
    {
        $this->qrbox = $qrbox;

        return $this;
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function getScanFields(): array
    {
        $raw = $this->evaluate($this->scanFields);
        if (! is_array($raw)) {
            return [];
        }

        $formatted = [];
        foreach ($raw as $key => $item) {
            if (is_string($item)) {
                $formatted[] = [
                    'key' => is_string($key) ? $key : $item,
                    'label' => ucwords(str_replace(['_', '-'], ' ', is_string($key) ? $item : $item)),
                ];
            } elseif (is_array($item)) {
                $itemKey = isset($item['key']) && is_string($item['key']) ? $item['key'] : (string) $key;
                $itemLabel = isset($item['label']) && is_string($item['label']) ? $item['label'] : ucwords(str_replace(['_', '-'], ' ', $itemKey));

                $formatted[] = [
                    'key' => $itemKey,
                    'label' => $itemLabel,
                ];
            }
        }

        return $formatted;
    }

    public function getFps(): int
    {
        return (int) $this->evaluate($this->fps);
    }

    public function getQrbox(): int
    {
        return (int) $this->evaluate($this->qrbox);
    }

    public function preferRearCamera(bool|Closure $condition = true): static
    {
        $this->preferRearCamera = $condition;

        return $this;
    }

    public function isPreferRearCamera(): bool
    {
        return (bool) $this->evaluate($this->preferRearCamera);
    }

    /**
     * @param  array<int, BarcodeFormat|string>|Closure  $formats
     */
    public function formats(array|Closure $formats): static
    {
        $this->supportedFormats = $formats;

        return $this;
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

    public function statePathPrefix(string|Closure $prefix): static
    {
        $this->statePathPrefix = $prefix;

        return $this;
    }

    public function getStatePathPrefix(): string
    {
        return (string) $this->evaluate($this->statePathPrefix);
    }

    public function scanFormat(?Closure $formatter): static
    {
        $this->scanFormatter = $formatter;

        return $this;
    }

    public function formatScannedValue(string $rawValue): mixed
    {
        if ($this->scanFormatter instanceof Closure) {
            return $this->evaluate($this->scanFormatter, ['rawValue' => $rawValue, 'state' => $rawValue]);
        }

        return $rawValue;
    }

    public function onStepScanned(?Closure $callback): static
    {
        $this->onStepCallback = $callback;

        return $this;
    }

    public function triggerOnStep(string $fieldKey, string $scannedValue): void
    {
        if ($this->onStepCallback instanceof Closure) {
            $this->evaluate($this->onStepCallback, ['field' => $fieldKey, 'scannedValue' => $scannedValue, 'component' => $this]);
        }
    }
}
