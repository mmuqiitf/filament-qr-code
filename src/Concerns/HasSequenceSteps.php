<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Events\ScanSequenceCompleted;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use Mmuqiitf\FilamentQrCode\Support\SequenceScanResult;
use Throwable;

/**
 * Server side of QrScanSequence steps: one handleStepScan() call per scan
 * checks the step, records the value and, on the last step, completes the set.
 */
trait HasSequenceSteps
{
    protected ?Closure $onCompletedCallback = null;

    protected bool|Closure|null $shouldAutoSubmit = null;

    protected string|Closure|null $autoSubmitMethod = null;

    /** @var array<int|string, mixed>|Closure|null */
    protected array|Closure|null $validateAllUsing = null;

    protected InvalidSequenceBehavior|Closure|null $invalidSequenceBehavior = null;

    protected bool|Closure|null $shouldResetAfterSubmit = null;

    protected bool|Closure|null $isContinuousSequence = null;

    /** @return array<int, ScanStep> */
    abstract public function getScanSteps(): array;

    /** @return array<string, mixed> */
    abstract public function getSequenceState(): array;

    abstract public function normalizeStepValue(string $fieldKey, mixed $value): mixed;

    abstract public function getStatePathPrefix(): string;

    abstract public function getComponentStatePath(): ?string;

    public function onCompleted(?Closure $callback): static
    {
        $this->onCompletedCallback = $callback;

        return $this;
    }

    /**
     * Check the whole set once every required step has a value. Rules are keyed
     * by step; unkeyed rules apply to the last step. A closure receives
     * $values and returns null or [] to pass, a message (last step) or [step => message].
     *
     * @param  array<int|string, mixed>|Closure|null  $rules
     */
    public function validateAllUsing(array|Closure|null $rules): static
    {
        $this->validateAllUsing = $rules;

        return $this;
    }

    public function onInvalidSequence(InvalidSequenceBehavior|Closure|null $behavior): static
    {
        $this->invalidSequenceBehavior = $behavior;

        return $this;
    }

    public function getInvalidSequenceBehavior(): InvalidSequenceBehavior
    {
        $behavior = $this->evaluate($this->invalidSequenceBehavior)
            ?? (function_exists('config') ? config('qr-code.sequence.on_invalid') : null);

        if ($behavior instanceof InvalidSequenceBehavior) {
            return $behavior;
        }

        return is_string($behavior)
            ? (InvalidSequenceBehavior::tryFrom($behavior) ?? InvalidSequenceBehavior::JumpToStep)
            : InvalidSequenceBehavior::JumpToStep;
    }

    public function resetAfterSubmit(bool|Closure|null $condition = true): static
    {
        $this->shouldResetAfterSubmit = $condition;

        return $this;
    }

    public function shouldResetAfterSubmit(): bool
    {
        if ($this->shouldResetAfterSubmit === null) {
            return ConfigValue::bool('qr-code.sequence.reset_after_submit', false);
        }

        return (bool) $this->evaluate($this->shouldResetAfterSubmit);
    }

    /** Start over after every completed sequence, ready for the next item. */
    public function continuous(bool|Closure|null $condition = true): static
    {
        $this->isContinuousSequence = $condition;

        return $this;
    }

    public function isContinuous(): bool
    {
        if ($this->isContinuousSequence === null) {
            return ConfigValue::bool('qr-code.sequence.continuous', false);
        }

        return (bool) $this->evaluate($this->isContinuousSequence);
    }

    public function autoSubmit(bool|Closure|null $condition = true, string|Closure|null $method = null): static
    {
        $this->shouldAutoSubmit = $condition;
        $this->autoSubmitMethod = $method;

        return $this;
    }

    public function shouldAutoSubmit(): bool
    {
        if ($this->shouldAutoSubmit === null) {
            return ConfigValue::bool('qr-code.sequence.auto_submit', false);
        }

        return (bool) $this->evaluate($this->shouldAutoSubmit);
    }

    public function getAutoSubmitMethod(): string
    {
        $method = $this->autoSubmitMethod === null
            ? (function_exists('config') ? config('qr-code.sequence.submit_method', 'submit') : 'submit')
            : $this->evaluate($this->autoSubmitMethod);

        return is_string($method) && $method !== '' ? $method : 'submit';
    }

    /**
     * True when a scan needs the server: any step check, a whole-set check,
     * onCompleted() or autoSubmit(). Without it the browser writes state directly, as before.
     */
    public function hasServerSequenceWork(): bool
    {
        foreach ($this->getScanSteps() as $step) {
            if ($step->hasChecks()) {
                return true;
            }
        }

        return $this->validateAllUsing !== null
            || $this->onCompletedCallback instanceof Closure
            || $this->shouldAutoSubmit()
            || $this->isContinuous();
    }

    /** @return array<string, mixed> */
    #[ExposedLivewireMethod]
    public function handleStepScan(string $step, string $code, string $source): array
    {
        $input = ScanInput::fromClient($source);
        $values = $this->getSequenceState();
        $index = $this->findStepIndex($step);

        if ($index === null) {
            return (new SequenceScanResult(
                scan: ScanResult::reject(trim($code), $input, __('filament-qr-code::ui.scan_rejected')),
                step: null,
                nextStep: null,
                values: $values,
            ))->toClient();
        }

        $steps = $this->getScanSteps();
        $normalized = $this->normalizeStepValue($step, trim($code));
        $code = is_scalar($normalized) ? trim((string) $normalized) : trim($code);
        $result = $this->checkStep($steps[$index], $code, $input, $values);
        $skipped = [];

        if (! $result->passes && $steps[$index]->isOptional() && isset($steps[$index + 1])) {
            // An optional step whose check fails gives way when the code fits the next step.
            $nextResult = $this->checkStep($steps[$index + 1], $code, $input, $values);

            if ($nextResult->passes) {
                $skipped = [$step];
                $result = $nextResult;
                $index++;
                $step = $steps[$index]->getKey();
            }
        }

        if (! $result->passes) {
            event(new ScanRejected($result, $step));

            return (new SequenceScanResult(scan: $result, step: $step, nextStep: $step, values: $values))->toClient();
        }

        foreach ($skipped as $skippedKey) {
            unset($values[$skippedKey]);
        }

        $values[$step] = $result->code;
        $this->writeSequenceValues($values);
        $this->dispatchAcceptedStep($result, $step);

        $nextStep = $this->findNextStep($index, $values);

        if ($nextStep !== null) {
            return (new SequenceScanResult(scan: $result, step: $step, nextStep: $nextStep, values: $values, skipped: $skipped))->toClient();
        }

        return $this->completeSequence($result, $step, $values, $skipped)->toClient();
    }

    /** @param  array<string, mixed>  $values */
    protected function checkStep(ScanStep $step, string $code, ScanInput $source, array $values): ScanResult
    {
        return $step->checkPipeline()->run(
            $code,
            $source,
            fn (Closure $closure, array $named = [], array $typed = []): mixed => $this->evaluate($closure, $named, $typed),
            $step->getLabel(),
            ['step' => $step, 'values' => $values],
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $skipped
     */
    protected function completeSequence(ScanResult $result, string $step, array $values, array $skipped = []): SequenceScanResult
    {
        if (($failure = $this->findInvalidStep($values)) !== null) {
            [$invalidStep, $message] = $failure;
            $restart = $this->getInvalidSequenceBehavior() === InvalidSequenceBehavior::Restart;

            if ($restart) {
                $values = [];
                $this->writeSequenceValues($values);
            }

            event(new ScanRejected(ScanResult::reject($result->code, $result->source, $message), $invalidStep));

            return new SequenceScanResult(
                scan: ScanResult::reject($result->code, $result->source, $message),
                step: $step,
                nextStep: $restart ? $this->getFirstStepKey() : $invalidStep,
                values: $values,
                skipped: $skipped,
                invalidStep: $invalidStep,
                reset: $restart,
            );
        }

        $this->evaluate($this->onCompletedCallback, ['values' => $values]);

        event(new ScanSequenceCompleted($values, $this->getComponentStatePath()));

        $submitted = $this->shouldAutoSubmit() ? $this->submitSequence() : null;
        $reset = ($submitted === true && $this->shouldResetAfterSubmit())
            || ($submitted !== false && $this->isContinuous());

        if ($reset) {
            $values = [];
            $this->writeSequenceValues($values);
        }

        return new SequenceScanResult(
            scan: $result,
            step: $step,
            nextStep: $reset ? $this->getFirstStepKey() : null,
            values: $values,
            skipped: $skipped,
            completed: true,
            submitted: $submitted,
            reset: $reset,
        );
    }

    protected function getFirstStepKey(): ?string
    {
        $steps = $this->getScanSteps();

        return isset($steps[0]) ? $steps[0]->getKey() : null;
    }

    protected function getLastStepKey(): string
    {
        $steps = array_values($this->getScanSteps());

        return $steps === [] ? '' : $steps[count($steps) - 1]->getKey();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{0: string, 1: string}|null
     */
    protected function findInvalidStep(array $values): ?array
    {
        $check = $this->validateAllUsing;

        if ($check === null) {
            return null;
        }

        $lastStep = $this->getLastStepKey();

        if ($check instanceof Closure) {
            $failures = $this->evaluate($check, ['values' => $values]);

            if (is_string($failures) && $failures !== '') {
                return [$lastStep, $failures];
            }

            if (! is_array($failures)) {
                return null;
            }

            foreach ($failures as $key => $message) {
                if (filled($message)) {
                    $key = is_string($key) && $this->findStepIndex($key) !== null ? $key : $lastStep;

                    return [$key, is_scalar($message) ? (string) $message : __('filament-qr-code::ui.scan_rejected')];
                }
            }

            return null;
        }

        $rules = [];

        foreach ($check as $key => $rule) {
            if (is_int($key)) {
                $rules[$lastStep][] = $rule;

                continue;
            }

            $rules[$key] = array_merge($rules[$key] ?? [], is_string($rule) ? explode('|', $rule) : (array) $rule);
        }

        $attributes = [];

        foreach ($this->getScanSteps() as $scanStep) {
            $attributes[$scanStep->getKey()] = $scanStep->getLabel();
        }

        $validator = Validator::make($values, $rules, [], $attributes);

        if (! $validator->fails()) {
            return null;
        }

        foreach ($this->getScanSteps() as $scanStep) {
            if (filled($message = $validator->errors()->first($scanStep->getKey()))) {
                return [$scanStep->getKey(), $message];
            }
        }

        return [$lastStep, (string) $validator->errors()->first()];
    }

    protected function submitSequence(): bool
    {
        $livewire = $this->getLivewire();

        try {
            $livewire->{$this->getAutoSubmitMethod()}();
        } catch (ValidationException $exception) {
            $livewire->setErrorBag($exception->validator->errors());

            return false;
        }

        return true;
    }

    protected function dispatchAcceptedStep(ScanResult $result, string $step): void
    {
        event(new ScanAccepted($result, $step));
        event(new QrCodeScanned(
            code: $result->code,
            source: ($result->source ?? ScanInput::Camera)->value,
            field: $step,
            context: ['record_key' => $result->record?->getKey()],
        ));
    }

    protected function findStepIndex(string $key): ?int
    {
        foreach ($this->getScanSteps() as $index => $step) {
            if ($step->getKey() === $key) {
                return $index;
            }
        }

        return null;
    }

    /**
     * The first empty step after $index; past the end, the first empty
     * required step. Null when every required step has a value.
     *
     * @param  array<string, mixed>  $values
     */
    protected function findNextStep(int $index, array $values): ?string
    {
        $steps = $this->getScanSteps();

        foreach (array_slice($steps, $index + 1) as $step) {
            if (blank($values[$step->getKey()] ?? null)) {
                return $step->getKey();
            }
        }

        foreach ($steps as $step) {
            if (! $step->isOptional() && blank($values[$step->getKey()] ?? null)) {
                return $step->getKey();
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $values */
    protected function allRequiredFilled(array $values): bool
    {
        foreach ($this->getScanSteps() as $step) {
            if (! $step->isOptional() && blank($values[$step->getKey()] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Write values the way the browser always has: the component's own state
     * path plus the legacy statePathPrefix path per step.
     *
     * @param  array<string, mixed>  $values
     */
    protected function writeSequenceValues(array $values): void
    {
        $livewire = $this->getLivewire();

        if (($statePath = $this->getComponentStatePath()) !== null) {
            data_set($livewire, $statePath, $values);
        }

        $prefix = trim($this->getStatePathPrefix(), '.');

        foreach ($this->getScanSteps() as $step) {
            $key = $step->getKey();
            $path = $prefix !== '' ? "{$prefix}.{$key}" : $key;

            try {
                data_set($livewire, $path, $values[$key] ?? null);
            } catch (Throwable) {
                // The legacy prefix path may not exist on this Livewire component.
            }
        }
    }
}
