<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Enums\RepeatComplete;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Events\ScanSequenceCompleted;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use Mmuqiitf\FilamentQrCode\Support\SequenceScanResult;
use Mmuqiitf\FilamentQrCode\Validation\SequenceSetRule;
use Mmuqiitf\FilamentQrCode\Validation\SequenceStepRule;
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

    protected bool $hasWarnedAboutAutomaticKey = false;

    protected bool|Closure|null $shouldResetAfterSubmit = null;

    protected bool|Closure|null $isContinuousSequence = null;

    protected ?Closure $onStepAcceptedCallback = null;

    protected ?Closure $onStepRejectedCallback = null;

    /** @return array<int, ScanStep> */
    abstract public function getScanSteps(): array;

    /** @return array<string, mixed> */
    abstract public function getSequenceState(): array;

    abstract public function normalizeStepValue(string $fieldKey, mixed $value): mixed;

    abstract public function getStatePathPrefix(): string;

    abstract public function getComponentStatePath(): ?string;

    abstract public function hasStatePath(): bool;

    /**
     * Side effect after a step accepts a live scan (log, notify). Receives
     * $step, $result, $code, $record, $values; its return value is ignored.
     */
    public function onStepAccepted(?Closure $callback): static
    {
        $this->onStepAcceptedCallback = $callback;

        return $this;
    }

    /** Side effect after a step rejects a scan. It can't change the result or the message. */
    public function onStepRejected(?Closure $callback): static
    {
        $this->onStepRejectedCallback = $callback;

        return $this;
    }

    /** @param  array<string, mixed>  $values */
    protected function runStepHook(?Closure $hook, ScanResult $result, string $step, array $values): void
    {
        if (! $hook instanceof Closure) {
            return;
        }

        try {
            $this->evaluate($hook, [
                'step' => $step,
                'result' => $result,
                'code' => $result->code,
                'record' => $result->record,
                'source' => $result->source,
                'values' => $values,
            ], [ScanResult::class => $result]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** @param  array<string, mixed>  $values */
    protected function rejectStep(ScanResult $result, string $step, array $values): void
    {
        event(new ScanRejected($result, $step, ...$this->stepEventContext($step, $values)));

        $this->runStepHook($this->onStepRejectedCallback, $result, $step, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{statePath: ?string, livewireClass: ?string, livewireId: ?string, sequence: ?string, values: array<string, mixed>}
     */
    protected function stepEventContext(string $step, array $values): array
    {
        [$livewireClass, $livewireId] = $this->livewireIdentity();

        return [
            'statePath' => $this->getStepValuePath($step),
            'livewireClass' => $livewireClass,
            'livewireId' => $livewireId,
            'sequence' => $this->getComponentStatePath(),
            'values' => $values,
        ];
    }

    /** @return array{0: ?string, 1: ?string} */
    protected function livewireIdentity(): array
    {
        try {
            $livewire = $this->getLivewire();
        } catch (Throwable) {
            return [null, null];
        }

        return [$livewire::class, $livewire->getId()];
    }

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
            if ($step->hasChecks() || $step->isSticky() || $step->isRepeating()) {
                return true;
            }
        }

        return $this->validateAllUsing !== null
            || $this->onCompletedCallback instanceof Closure
            || $this->shouldAutoSubmit()
            || $this->isContinuous();
    }

    /**
     * Where each step's value lives in Livewire state: the component's own
     * state path when it has one, else the legacy statePathPrefix paths.
     */
    public function getStepValuePath(string $key): string
    {
        if (($statePath = $this->getComponentStatePath()) !== null) {
            return "{$statePath}.{$key}";
        }

        $prefix = trim($this->getStatePathPrefix(), '.');

        return $prefix !== '' ? "{$prefix}.{$key}" : $key;
    }

    /**
     * A normal form submit re-runs every step check and the whole-set check,
     * like ScanChecksRule does for QrScanner.
     *
     * @param  array<string, array<mixed>>  $rules
     */
    public function dehydrateValidationRules(array &$rules): void
    {
        foreach ($this->getScanSteps() as $step) {
            if ($step->hasChecks()) {
                $rules[$this->getStepValuePath($step->getKey())][] = new SequenceStepRule($this, $step);
            }
        }

        if ($this->validateAllUsing !== null && $this->getScanSteps() !== []) {
            $rules[$this->getStepValuePath($this->getLastStepKey())][] = new SequenceSetRule($this);
        }
    }

    /** @param  array<string, string>  $attributes */
    public function dehydrateValidationAttributes(array &$attributes): void
    {
        foreach ($this->getScanSteps() as $step) {
            $attributes[$this->getStepValuePath($step->getKey())] = $step->getLabel();
        }
    }

    /** @param  array<string, array<string, string>>  $messages */
    public function dehydrateValidationMessages(array &$messages): void {}

    /**
     * Key for a sequence that needs the server but has neither statePath()
     * nor key(): without one its checks could not run.
     */
    public function getAutomaticKey(): ?string
    {
        if ($this->hasStatePath() || ! $this->hasServerSequenceWork()) {
            return null;
        }

        $key = 'qr-scan-sequence-'.implode('-', array_map(
            fn (ScanStep $step): string => Str::slug($step->getKey()),
            $this->getScanSteps(),
        ));

        if (! $this->hasWarnedAboutAutomaticKey && app()->isLocal()) {
            $this->hasWarnedAboutAutomaticKey = true;
            Log::warning("QrScanSequence has server checks but no statePath() or key(); using the automatic key [{$key}]. Give it a statePath() or key() so two sequences never share one.");
        }

        return $key;
    }

    /** @return array<string, mixed> */
    #[ExposedLivewireMethod]
    public function handleStepScan(string $step, string $code, string $source): array
    {
        $input = ScanInput::fromClient($source);
        $values = $this->withoutInactiveSteps($this->getSequenceState());
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

        if (! $this->isStepActive($steps[$index], $values)) {
            $result = ScanResult::reject(trim($code), $input, __('filament-qr-code::ui.step_inactive'));
            $this->rejectStep($result, $step, $values);

            return (new SequenceScanResult(scan: $result, step: $step, nextStep: $this->getFirstEmptyStepKey($values), values: $values))->toClient();
        }

        $code = $this->normalizeStepCode($steps[$index], $code, $values);
        if ($steps[$index]->isRepeating()) {
            return $this->handleRepeatScan($index, $code, $input, $values)->toClient();
        }

        $result = $this->checkStep($steps[$index], $code, $input, $values);
        $skipped = [];
        $nextIndex = $this->findNextActiveIndex($index, $values);

        if (! $result->passes && $input !== ScanInput::Manual && $steps[$index]->isOptional() && $nextIndex !== null) {
            // An optional step whose check fails gives way when the code fits the next active step.
            $nextStep = $steps[$nextIndex];
            $nextResult = $nextStep->isRepeating()
                ? $this->checkRepeatScan($nextStep, $code, $input, $values)
                : $this->checkStep($nextStep, $code, $input, $values);

            if ($nextResult->passes && $nextStep->isRepeating()) {
                unset($values[$step]);

                return $this->acceptRepeatScan($nextIndex, $nextResult, $values, [$step])->toClient();
            }

            if ($nextResult->passes) {
                $skipped = [$step];
                $result = $nextResult;
                $index = $nextIndex;
                $step = $nextStep->getKey();
            }
        }

        if (! $result->passes) {
            $this->rejectStep($result, $step, $values);

            return (new SequenceScanResult(scan: $result, step: $step, nextStep: $step, values: $values))->toClient();
        }

        foreach ($skipped as $skippedKey) {
            unset($values[$skippedKey]);
        }

        $values[$step] = $result->code;
        $this->writeSequenceValues($values);
        $this->dispatchAcceptedStep($result, $step, $values);

        $nextStep = $this->findNextStep($index, $values);

        if ($nextStep !== null) {
            return (new SequenceScanResult(scan: $result, step: $step, nextStep: $nextStep, values: $values, skipped: $skipped))->toClient();
        }

        return $this->completeSequence($result, $step, $values, $skipped)->toClient();
    }

    /**
     * Finish a waiting or open-ended repeat step (the Done control).
     *
     * @return array<string, mixed>
     */
    #[ExposedLivewireMethod]
    public function finishStep(string $step): array
    {
        $values = $this->withoutInactiveSteps($this->getSequenceState());
        $index = $this->findStepIndex($step);

        if ($index === null || ! $this->getScanSteps()[$index]->isRepeating()) {
            return (new SequenceScanResult(
                scan: ScanResult::reject('', ScanInput::Manual, __('filament-qr-code::ui.scan_rejected')),
                step: $index === null ? null : $step,
                nextStep: $index === null ? null : $step,
                values: $values,
            ))->toClient();
        }

        return $this->finishRepeatStep($index, '', ScanInput::Manual, $values)->toClient();
    }

    /**
     * Remove one collected code from a repeat step (a mis-scan).
     *
     * @return array<string, mixed>
     */
    #[ExposedLivewireMethod]
    public function removeStepCode(string $step, int $index): array
    {
        $values = $this->getSequenceState();
        $stepIndex = $this->findStepIndex($step);

        if ($stepIndex === null || ! $this->getScanSteps()[$stepIndex]->isRepeating()) {
            return $this->rejectStepAction($step, $values);
        }

        $codes = $this->repeatCodes($values, $step);
        $removed = $codes[$index] ?? '';
        unset($codes[$index]);
        $values[$step] = array_values($codes);

        if ($values[$step] === []) {
            unset($values[$step]);
        }

        $this->writeSequenceValues($values);

        return (new SequenceScanResult(scan: ScanResult::accept($removed, ScanInput::Manual), step: $step, nextStep: $step, values: $values))->toClient();
    }

    /**
     * Clear a step (the Clear control on sticky steps) and ask for it again.
     *
     * @return array<string, mixed>
     */
    #[ExposedLivewireMethod]
    public function clearStep(string $step): array
    {
        $values = $this->getSequenceState();

        if ($this->findStepIndex($step) === null) {
            return $this->rejectStepAction($step, $values);
        }

        unset($values[$step]);
        $this->writeSequenceValues($values);

        return (new SequenceScanResult(scan: ScanResult::accept('', ScanInput::Manual), step: $step, nextStep: $step, values: $values))->toClient();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function rejectStepAction(string $step, array $values): array
    {
        $known = $this->findStepIndex($step) !== null;

        return (new SequenceScanResult(
            scan: ScanResult::reject('', ScanInput::Manual, __('filament-qr-code::ui.scan_rejected')),
            step: $known ? $step : null,
            nextStep: $known ? $step : null,
            values: $values,
        ))->toClient();
    }

    /** @param  array<string, mixed>  $values */
    protected function handleRepeatScan(int $index, string $code, ScanInput $input, array $values): SequenceScanResult
    {
        $step = $this->getScanSteps()[$index];
        $key = $step->getKey();

        if ($code !== '' && $code === $this->getFinishCode($step)) {
            return $this->finishRepeatStep($index, $code, $input, $values);
        }

        $result = $this->checkRepeatScan($step, $code, $input, $values);

        if (! $result->passes) {
            $this->rejectStep($result, $key, $values);

            return new SequenceScanResult(scan: $result, step: $key, nextStep: $key, values: $values);
        }

        return $this->acceptRepeatScan($index, $result, $values);
    }

    /**
     * The repeat step's own rules (full, duplicate) then its checks, without side effects.
     *
     * @param  array<string, mixed>  $values
     */
    protected function checkRepeatScan(ScanStep $step, string $code, ScanInput $input, array $values): ScanResult
    {
        $codes = $this->repeatCodes($values, $step->getKey());
        $limit = $this->getRepeatLimit($step, $values);

        return match (true) {
            $limit !== null && count($codes) >= $limit => ScanResult::reject($code, $input, __('filament-qr-code::ui.repeat_full', ['count' => $limit])),
            ! $step->allowsDuplicates() && in_array($code, $codes, true) => ScanResult::reject($code, $input, __('filament-qr-code::ui.duplicate_in_step', ['code' => $code])),
            default => $this->checkStep($step, $code, $input, $values),
        };
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $skipped
     */
    protected function acceptRepeatScan(int $index, ScanResult $result, array $values, array $skipped = []): SequenceScanResult
    {
        $step = $this->getScanSteps()[$index];
        $key = $step->getKey();

        $values[$key] = [...$this->repeatCodes($values, $key), $result->code];
        $this->writeSequenceValues($values);
        $this->dispatchAcceptedStep($result, $key, $values);

        $limit = $this->getRepeatLimit($step, $values);
        $isFull = $limit !== null && count($values[$key]) >= $limit;

        if (! $isFull || $this->getRepeatComplete($step) === RepeatComplete::Wait) {
            return new SequenceScanResult(scan: $result, step: $key, nextStep: $key, values: $values, skipped: $skipped);
        }

        return $this->advanceFrom($index, $result, $key, $values, $skipped);
    }

    /** @param  array<string, mixed>  $values */
    protected function finishRepeatStep(int $index, string $code, ScanInput $input, array $values): SequenceScanResult
    {
        $step = $this->getScanSteps()[$index];
        $key = $step->getKey();
        $limit = $this->getRepeatLimit($step, $values);
        $needed = $limit ?? 1;
        $count = count($this->repeatCodes($values, $key));

        if ($count < $needed && ! ($step->isOptional() && $count === 0)) {
            $result = ScanResult::reject($code, $input, __('filament-qr-code::ui.repeat_needs_codes', ['count' => $needed]));
            $this->rejectStep($result, $key, $values);

            return new SequenceScanResult(scan: $result, step: $key, nextStep: $key, values: $values);
        }

        $sequenceResult = $this->advanceFrom($index, ScanResult::accept($code, $input), $key, $values);

        return new SequenceScanResult(
            scan: $sequenceResult->scan,
            step: $sequenceResult->step,
            nextStep: $sequenceResult->nextStep,
            values: $sequenceResult->values,
            skipped: $sequenceResult->skipped,
            completed: $sequenceResult->completed,
            invalidStep: $sequenceResult->invalidStep,
            submitted: $sequenceResult->submitted,
            reset: $sequenceResult->reset,
            finished: true,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $skipped
     */
    protected function advanceFrom(int $index, ScanResult $result, string $key, array $values, array $skipped = []): SequenceScanResult
    {
        $nextStep = $this->findNextStep($index, $values);

        if ($nextStep !== null) {
            return new SequenceScanResult(scan: $result, step: $key, nextStep: $nextStep, values: $values, skipped: $skipped);
        }

        return $this->completeSequence($result, $key, $values, $skipped);
    }

    /**
     * A repeat step is filled with its count of codes, or one code when open-ended.
     *
     * @param  array<string, mixed>  $values
     */
    public function isStepFilled(ScanStep $step, array $values): bool
    {
        if (! $step->isRepeating()) {
            return filled($values[$step->getKey()] ?? null);
        }

        $count = count($this->repeatCodes($values, $step->getKey()));

        return $count >= ($this->getRepeatLimit($step, $values) ?? 1);
    }

    /** @param  array<string, mixed>  $values */
    public function getRepeatLimit(ScanStep $step, array $values): ?int
    {
        $override = $this->getRepeatCountOverride($step->getKey());

        if ($override !== null) {
            return $override;
        }

        try {
            $times = $this->evaluate($step->getRepeatTimesUsing(), ['values' => $values]);
        } catch (Throwable $exception) {
            // Usually a count that reads a step with no value yet: stay open-ended until it has one.
            if (app()->isLocal()) {
                Log::warning("QrScanSequence step [{$step->getKey()}]: the repeat() count closure failed ({$exception->getMessage()}); treating the step as open-ended until its values exist.");
            }

            return null;
        }

        return is_numeric($times) ? max(1, (int) $times) : null;
    }

    public function getRepeatComplete(ScanStep $step): RepeatComplete
    {
        if ($step->getRepeatTimesUsing() === null) {
            return RepeatComplete::Wait;
        }

        $behavior = $this->evaluate($step->getRepeatCompleteUsing())
            ?? (function_exists('config') ? config('qr-code.sequence.repeat_complete') : null);

        if ($behavior instanceof RepeatComplete) {
            return $behavior;
        }

        return is_string($behavior) ? (RepeatComplete::tryFrom($behavior) ?? RepeatComplete::Advance) : RepeatComplete::Advance;
    }

    public function getFinishCode(ScanStep $step): ?string
    {
        $code = $this->evaluate($step->getFinishCodeUsing())
            ?? (function_exists('config') ? config('qr-code.sequence.finish_code') : null);

        return is_string($code) && trim($code) !== '' ? trim($code) : null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    protected function repeatCodes(array $values, string $key): array
    {
        $codes = $values[$key] ?? [];

        if (is_string($codes)) {
            return $codes === '' ? [] : [$codes];
        }

        return is_array($codes) ? array_values(array_map(strval(...), array_filter($codes, is_scalar(...)))) : [];
    }

    /**
     * The code a step checks: the normalizeWith() presets, the sequence's
     * normalizeStepUsing(), then the step's own normalizeUsing().
     *
     * @param  array<string, mixed>  $values
     */
    public function normalizeStepCode(ScanStep $step, string $code, array $values): string
    {
        $code = $this->applyCodeNormalizer($code);
        $normalized = $this->normalizeStepValue($step->getKey(), $code);
        $code = is_scalar($normalized) ? trim((string) $normalized) : $code;

        if (! ($normalizer = $step->getNormalizeUsing()) instanceof Closure) {
            return $code;
        }

        $normalized = $this->evaluate($normalizer, ['code' => $code, 'rawValue' => $code, 'state' => $code, 'step' => $step, 'values' => $values]);

        return is_scalar($normalized) ? trim((string) $normalized) : $code;
    }

    /** Provided by HasCodeNormalization. */
    abstract protected function applyCodeNormalizer(string $code): string;

    /** @param  array<string, mixed>  $values */
    public function checkStep(ScanStep $step, string $code, ?ScanInput $source, array $values): ScanResult
    {
        return $step->checkPipeline($this)->run(
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
                $values = $this->stickyValues($values);
                $this->writeSequenceValues($values);
            }

            $this->rejectStep(ScanResult::reject($result->code, $result->source, $message), $invalidStep, $values);

            return new SequenceScanResult(
                scan: ScanResult::reject($result->code, $result->source, $message),
                step: $step,
                nextStep: $restart ? $this->getFirstEmptyStepKey($values) : $invalidStep,
                values: $values,
                skipped: $skipped,
                invalidStep: $invalidStep,
                reset: $restart,
            );
        }

        $this->evaluate($this->onCompletedCallback, ['values' => $values]);

        [$livewireClass, $livewireId] = $this->livewireIdentity();
        event(new ScanSequenceCompleted($values, $this->getComponentStatePath(), $livewireClass, $livewireId));

        $submitted = $this->shouldAutoSubmit() ? $this->submitSequence() : null;
        $reset = ($submitted === true && $this->shouldResetAfterSubmit())
            || ($submitted !== false && $this->isContinuous());

        if ($reset) {
            $values = $this->stickyValues($values);
            $this->writeSequenceValues($values);
        }

        return new SequenceScanResult(
            scan: $result,
            step: $step,
            nextStep: $reset ? $this->getFirstEmptyStepKey($values) : null,
            values: $values,
            skipped: $skipped,
            completed: true,
            submitted: $submitted,
            reset: $reset,
        );
    }

    /**
     * The values a reset keeps: sticky steps only.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function stickyValues(array $values): array
    {
        $kept = [];

        foreach ($this->getScanSteps() as $step) {
            if ($step->isSticky() && filled($values[$step->getKey()] ?? null)) {
                $kept[$step->getKey()] = $values[$step->getKey()];
            }
        }

        return $kept;
    }

    /** @param  array<string, mixed>  $values */
    protected function getFirstEmptyStepKey(array $values): ?string
    {
        foreach ($this->getScanSteps() as $step) {
            if ($this->isStepActive($step, $values) && ! $this->isStepFilled($step, $values)) {
                return $step->getKey();
            }
        }

        return $this->getFirstStepKey();
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
    public function findInvalidStep(array $values): ?array
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

    /** @param  array<string, mixed>  $values */
    protected function dispatchAcceptedStep(ScanResult $result, string $step, array $values = []): void
    {
        event(new ScanAccepted($result, $step, ...$this->stepEventContext($step, $values)));
        $this->runStepHook($this->onStepAcceptedCallback, $result, $step, $values);
        event(new QrCodeScanned(
            code: $result->code,
            source: ($result->source ?? ScanInput::Camera)->value,
            field: $step,
            context: ['record_key' => $result->recordKey()],
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
            if ($this->isStepActive($step, $values) && ! $this->isStepFilled($step, $values)) {
                return $step->getKey();
            }
        }

        foreach ($steps as $step) {
            if ($this->isRequiredStep($step, $values) && ! $this->isStepFilled($step, $values)) {
                return $step->getKey();
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $values */
    protected function findNextActiveIndex(int $index, array $values): ?int
    {
        foreach (array_slice($this->getScanSteps(), $index + 1, preserve_keys: true) as $nextIndex => $step) {
            if ($this->isStepActive($step, $values)) {
                return $nextIndex;
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $values */
    public function allRequiredFilled(array $values): bool
    {
        foreach ($this->getScanSteps() as $step) {
            if ($this->isRequiredStep($step, $values) && ! $this->isStepFilled($step, $values)) {
                return false;
            }
        }

        return true;
    }

    /** @param  array<string, mixed>  $values */
    protected function isRequiredStep(ScanStep $step, array $values): bool
    {
        return ! $step->isOptional() && $this->isStepActive($step, $values);
    }

    /**
     * A step is active unless its when() condition returns false. A condition
     * that throws counts as active, so a broken closure never hides a step.
     *
     * @param  array<string, mixed>  $values
     */
    public function isStepActive(ScanStep $step, array $values): bool
    {
        $condition = $step->getWhenUsing();

        if ($condition === null) {
            return true;
        }

        try {
            return (bool) $this->evaluate($condition, ['values' => $values, 'step' => $step]);
        } catch (Throwable $exception) {
            if (app()->isLocal()) {
                Log::warning("QrScanSequence step [{$step->getKey()}]: the when() condition failed ({$exception->getMessage()}); treating the step as active.");
            }

            return true;
        }
    }

    /**
     * Drop the values of inactive steps, deciding in step order so a step's
     * condition sees the values that remain before it.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function withoutInactiveSteps(array $values): array
    {
        foreach ($this->getScanSteps() as $step) {
            if (! $this->isStepActive($step, $values)) {
                unset($values[$step->getKey()]);
            }
        }

        return $values;
    }

    /** Move the browser to a step (page code). */
    public function goToStep(string $step): void
    {
        $this->ensureStepExists($step);

        $this->getLivewire()->dispatch('qr-sequence-go-to', field: $this->getControlKey(), step: $step);
    }

    /**
     * Start the sequence over from page code: sticky values stay unless
     * $keepSticky is false; $values seeds steps.
     *
     * @param  array<string, mixed>  $values
     */
    public function resetSequence(bool $keepSticky = true, array $values = []): void
    {
        $kept = $keepSticky ? $this->stickyValues($this->getSequenceState()) : [];
        $values = [...$kept, ...$values];

        $this->writeSequenceValues($values);

        $this->getLivewire()->dispatch('qr-sequence-reset', field: $this->getControlKey(), values: $values, fromServer: true);
    }

    /**
     * Override a repeat step's count for this page until set back to null.
     * Stored in the session so later server checks agree with the browser.
     */
    public function setRepeatCount(string $step, ?int $count): void
    {
        $index = $this->ensureStepExists($step);

        if (! $this->getScanSteps()[$index]->isRepeating()) {
            throw new InvalidArgumentException("QrScanSequence step [{$step}] does not repeat.");
        }

        $count === null
            ? session()->forget($this->repeatOverrideSessionKey($step))
            : session()->put($this->repeatOverrideSessionKey($step), max(1, $count));

        $this->getLivewire()->dispatch('qr-sequence-refresh', field: $this->getControlKey());
    }

    public function getRepeatCountOverride(string $step): ?int
    {
        try {
            $count = session()->get($this->repeatOverrideSessionKey($step));
        } catch (Throwable) {
            return null;
        }

        return is_int($count) ? $count : null;
    }

    protected function repeatOverrideSessionKey(string $step): string
    {
        return 'filament-qr-code.repeat.'.$this->getLivewire()->getId().'.'.$this->getControlKey().'.'.$step;
    }

    /** The key page events use to target this sequence: its schema key, else its state path. */
    public function getControlKey(): string
    {
        return (string) ($this->getKey() ?? $this->getComponentStatePath() ?? '');
    }

    protected function ensureStepExists(string $step): int
    {
        return $this->findStepIndex($step)
            ?? throw new InvalidArgumentException("QrScanSequence has no step [{$step}].");
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
