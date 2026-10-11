<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;

class QrScanSequence extends Component
{
    use HasCameraScanning;
    use HasFeedback;
    use HasHardwareScanner;
    use HasScanPayload;

    protected string $view = 'filament-qr-code::components.qr-scan-sequence';

    /**
     * @var array<int, array{key: string, label: string}|string>|Closure
     */
    protected array|Closure $scanFields = [];

    protected string|Closure $statePathPrefix = 'data';

    protected ?Closure $scanFormatter = null;

    protected ?Closure $onStepCallback = null;

    protected ?Closure $stepNormalizer = null;

    protected bool|Closure $allowEdit = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }

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

    /**
     * Programmatic-only step formatter. Runs via formatScannedValue() /
     * triggerOnStep() in custom flows — never on live camera/hardware scans.
     * For live values use normalizeStepUsing() (applied on merge) or
     * afterStateUpdated() on real fields.
     */
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

    /**
     * Live-step normalizer, applied by getSequenceState() and
     * mergeSequenceState() (e.g. strtoupper(trim($value))). This is the
     * live-scan counterpart to the programmatic-only scanFormat().
     */
    public function normalizeStepUsing(?Closure $normalizer): static
    {
        $this->stepNormalizer = $normalizer;

        return $this;
    }

    public function normalizeStepValue(string $fieldKey, mixed $value): mixed
    {
        if ($this->stepNormalizer instanceof Closure) {
            return $this->evaluate($this->stepNormalizer, [
                'field' => $fieldKey,
                'rawValue' => $value,
                'state' => $value,
            ]);
        }

        return $value;
    }

    /**
     * Programmatic-only step callback. Invoke via triggerOnStep() in custom
     * flows — live scans dispatch the `qr-sequence-step` window event and
     * write Livewire state directly instead.
     */
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

    /**
     * Loud mismatch warning: the legacy statePathPrefix() must equal the
     * schema's statePath(), otherwise container writes land outside the form
     * state. Returns the offending pair, or null when they agree (or when
     * the component is not mounted yet).
     *
     * @return array{prefix: string, container: string}|null
     */
    public function prefixMismatchWarning(): ?array
    {
        try {
            $prefix = trim($this->getStatePathPrefix(), '.');
            $container = (string) $this->getContainer()->getStatePath();
        } catch (\Throwable) {
            return null;
        }

        if ($prefix === $container) {
            return null;
        }

        return ['prefix' => $prefix, 'container' => $container];
    }

    /**
     * Absolute Livewire path of this component's own state (null when no
     * statePath is configured). Passed to Alpine as componentStatePath so
     * every scan syncs both the legacy prefix paths and this component.
     */
    public function getComponentStatePath(): ?string
    {
        try {
            if (! $this->hasStatePath()) {
                return null;
            }

            return $this->getStatePath();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Read current sequence values from Livewire, preferring this
     * component's own state and falling back to the legacy
     * statePathPrefix paths. Applies normalizeStepUsing().
     *
     * @return array<string, mixed>
     */
    public function getSequenceState(): array
    {
        $keys = array_map(fn ($field) => $field['key'], $this->getScanFields());
        $result = [];

        try {
            $ownState = $this->hasStatePath() ? $this->getState() : null;
        } catch (\Throwable) {
            return $result;
        }

        if (is_array($ownState)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $ownState) && filled($ownState[$key])) {
                    $result[$key] = $this->normalizeStepValue($key, $ownState[$key]);
                }
            }

            if ($result !== []) {
                return $result;
            }
        }

        try {
            $prefix = trim($this->getStatePathPrefix(), '.');
            $livewire = $this->getLivewire();
        } catch (\Throwable) {
            return $result;
        }

        foreach ($keys as $key) {
            $path = $prefix !== '' ? "{$prefix}.{$key}" : $key;
            $value = data_get($livewire, $path);

            if (filled($value)) {
                $result[$key] = $this->normalizeStepValue($key, $value);
            }
        }

        return $result;
    }

    /**
     * Merge sequence values into a form state array so callers no longer
     * hand-roll array_merge($this->data, $this->form->getState()).
     *
     * @param  array<string, mixed>  $formState
     * @return array<string, mixed>
     */
    public function mergeSequenceState(array $formState): array
    {
        foreach ($this->getSequenceState() as $key => $value) {
            if (! array_key_exists($key, $formState) || blank($formState[$key])) {
                $formState[$key] = $value;
            }
        }

        return $formState;
    }

    /**
     * @param  array<string, mixed>|null  $mergedState
     * @return array<int, string>
     */
    public function getMissingSequenceKeys(?array $mergedState = null): array
    {
        $state = $mergedState ?? $this->mergeSequenceState([]);
        $missing = [];

        foreach ($this->getScanFields() as $field) {
            if (blank($state[$field['key']] ?? null)) {
                $missing[] = $field['key'];
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>|null  $mergedState
     */
    public function isSequenceComplete(?array $mergedState = null): bool
    {
        return $this->getMissingSequenceKeys($mergedState) === [];
    }
}
