<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;

class QrScanSequence extends Component
{
    use HasCameraScanning;
    use HasFeedback;
    use HasHardwareScanner;

    protected string $view = 'filament-qr-code::components.qr-scan-sequence';

    /**
     * @var array<int, array{key: string, label: string}|string>|Closure
     */
    protected array|Closure $scanFields = [];

    protected string|Closure $statePathPrefix = 'data';

    protected ?Closure $scanFormatter = null;

    protected ?Closure $onStepCallback = null;

    protected bool|Closure $allowEdit = true;

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

    /**
     * Allow operators to correct a captured value inline (unedited vs edited modes).
     */
    public function editable(bool|Closure $condition = true): static
    {
        $this->allowEdit = $condition;

        return $this;
    }

    public function isEditable(): bool
    {
        return (bool) $this->evaluate($this->allowEdit);
    }
}
