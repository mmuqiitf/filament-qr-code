<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Contracts\HasValidationRules;
use Filament\Schemas\Components\Component;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasCodeNormalization;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasOptionsSync;
use Mmuqiitf\FilamentQrCode\Concerns\HasRenderHookScopes;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Concerns\HasSequenceSteps;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use ReflectionFunction;

class QrScanSequence extends Component implements HasValidationRules
{
    use HasCameraScanning;
    use HasCodeNormalization;
    use HasFeedback;
    use HasHardwareScanner;
    use HasOptionsSync;
    use HasRenderHookScopes;
    use HasScanPayload;
    use HasSequenceSteps;

    protected string $view = 'filament-qr-code::components.qr-scan-sequence';

    /**
     * @var array<int|string, array{key: string, label: string}|string|ScanStep>|Closure
     */
    protected array|Closure $scanFields = [];

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
        $static->configure();
        $static->fields($fields);
        // Only used without statePath(): a key() call replaces it.
        $static->key(fn (QrScanSequence $component): ?string => $component->getAutomaticKey());

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
     * @param  array<int, ScanStep>|Closure  $steps
     */
    public function steps(array|Closure $steps): static
    {
        $this->scanFields = $steps;

        return $this;
    }

    /**
     * Steps from steps() or adapted from the legacy fields() shapes.
     *
     * @return array<int, ScanStep>
     */
    public function getScanSteps(): array
    {
        $raw = $this->evaluate($this->scanFields);

        if (! is_array($raw)) {
            return [];
        }

        $steps = [];

        foreach ($raw as $index => $item) {
            $step = $item instanceof ScanStep ? $item : ScanStep::fromField($index, $item);

            if ($step instanceof ScanStep) {
                $steps[] = $step->evaluateUsing(fn (Closure $closure, ScanStep $step): mixed => $this->evaluateStepClosure($closure, $step));
            }
        }

        return $steps;
    }

    protected bool $isReadingStepValues = false;

    /**
     * Evaluate a ScanStep closure with Filament's injections plus $step and,
     * when the closure asks for it, $values. While values are being read a
     * nested request gets [], so a label that reads $values can't recurse.
     *
     * @internal Not covered by semver.
     */
    public function evaluateStepClosure(Closure $closure, ScanStep $step): mixed
    {
        $named = ['step' => $step];

        if ($this->closureWantsValues($closure)) {
            $named['values'] = $this->readStepValues();
        }

        return $this->evaluate($closure, $named, [ScanStep::class => $step]);
    }

    protected function closureWantsValues(Closure $closure): bool
    {
        foreach ((new ReflectionFunction($closure))->getParameters() as $parameter) {
            if ($parameter->getName() === 'values') {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed> */
    protected function readStepValues(): array
    {
        if ($this->isReadingStepValues) {
            return [];
        }

        $this->isReadingStepValues = true;

        try {
            return $this->getSequenceState();
        } finally {
            $this->isReadingStepValues = false;
        }
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function getScanFields(): array
    {
        return array_map(fn (ScanStep $step): array => $step->toField(), $this->getScanSteps());
    }

    /**
     * The schema state path the flat per-step paths live under: each step
     * value is also written to "{prefix}.{key}" for forms that read the steps
     * as separate fields. Derived from the parent schema, so it always
     * matches the form state.
     */
    public function getFlatStatePrefix(): string
    {
        try {
            return trim((string) $this->getContainer()->getStatePath(), '.');
        } catch (\Throwable) {
            return '';
        }
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

    /**
     * @internal Not covered by semver.
     */
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
     * Absolute Livewire path of this component's own state (null when no
     * statePath is configured). Passed to Alpine as componentStatePath so
     * every scan syncs both the flat per-step paths and this component.
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
     * component's own state and falling back to the flat per-step
     * paths. Applies normalizeStepUsing().
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
            $prefix = $this->getFlatStatePrefix();
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

        foreach ($this->getScanSteps() as $step) {
            if (! $this->isStepActive($step, $state)) {
                continue;
            }

            if (blank($state[$step->getKey()] ?? null)) {
                $missing[] = $step->getKey();
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

    /** @return array<string, mixed> */
    public function getComponentScanPayload(): array
    {
        $key = $this->getKey();

        return [
            'fields' => $this->getScanFields(),
            'statePrefix' => $this->getFlatStatePrefix(),
            'componentStatePath' => $this->getComponentStatePath(),
            'editable' => $this->isEditable(),
            // Server work needs a key to reach handleStepScan: statePath() or key().
            'serverCheck' => filled($key) && $this->hasServerSequenceWork(),
            'scanFailedMessage' => __('filament-qr-code::ui.scan_failed'),
            'steps' => $this->getStepsPayload(),
            'values' => $this->withoutInactiveSteps($this->getSequenceState()),
            ...(filled($key) ? ['componentKey' => $key] : []),
        ];
    }

    /** @return list<array{key: string, optional: bool, sticky: bool, repeating: bool, repeat: ?int, active: bool}> */
    public function getStepsPayload(): array
    {
        $values = $this->withoutInactiveSteps($this->getSequenceState());

        return array_values(array_map(fn (ScanStep $step): array => [
            'key' => $step->getKey(),
            'optional' => $step->isOptional(),
            'sticky' => $step->isSticky(),
            'repeating' => $step->isRepeating(),
            'repeat' => $step->isRepeating() ? $this->getRepeatLimit($step, $values) : null,
            'active' => $this->isStepActive($step, $values),
        ], $this->getScanSteps()));
    }
}
