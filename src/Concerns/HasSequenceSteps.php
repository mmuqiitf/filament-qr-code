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
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;
use Mmuqiitf\FilamentQrCode\Support\Gs1ScanCheck;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use Mmuqiitf\FilamentQrCode\Support\SequenceEngine;
use Mmuqiitf\FilamentQrCode\Support\StepEvaluator;
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

    /**
     * @internal Not covered by semver.
     */
    abstract public function normalizeStepValue(string $fieldKey, mixed $value): mixed;

    abstract public function hasStepNormalizer(): bool;

    abstract public function getFlatStatePrefix(): string;

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

    /** Callback run with every step value once the whole sequence is complete. */
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

    /** What a failed validateAllUsing() check does: jump back to the failing step or restart. */
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

    /** Clear the non-sticky steps after a successful submit. */
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

    /** Call the Livewire submit method once the sequence completes. */
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
        if ($this->isGs1()) {
            return true;
        }

        foreach ($this->getScanSteps() as $step) {
            if ($step->hasChecks() || $step->isSticky() || $step->isRepeating() || $step->getNormalizeUsing() instanceof Closure) {
                return true;
            }
        }

        // A step normalizer only runs on the server: without the round trip the
        // step list would show the raw code until the form reads its state.
        return $this->validateAllUsing !== null
            || $this->hasStepNormalizer()
            || $this->onCompletedCallback instanceof Closure
            || $this->shouldAutoSubmit()
            || $this->isContinuous();
    }

    /**
     * Where each step's value lives in Livewire state: the component's own
     * state path when it has one, else the flat per-step path.
     */
    public function getStepValuePath(string $key): string
    {
        if (($statePath = $this->getComponentStatePath()) !== null) {
            return "{$statePath}.{$key}";
        }

        $prefix = $this->getFlatStatePrefix();

        return $prefix !== '' ? "{$prefix}.{$key}" : $key;
    }

    /**
     * A normal form submit re-runs every step check and the whole-set check,
     * like ScanChecksRule does for QrScanner.
     *
     * @param  array<string, array<mixed>>  $rules
     *
     * @internal Public only because Filament calls this validation hook;
     * not covered by semver.
     */
    public function dehydrateValidationRules(array &$rules): void
    {
        foreach ($this->getScanSteps() as $step) {
            // Repeat steps too: submit re-reads their live count.
            if ($step->hasChecks() || $step->isRepeating() || $this->gs1CheckFor($step) !== null) {
                $rules[$this->getStepValuePath($step->getKey())][] = new SequenceStepRule($this, $step);
            }
        }

        if ($this->validateAllUsing !== null && $this->getScanSteps() !== []) {
            $rules[$this->getStepValuePath($this->getLastStepKey())][] = new SequenceSetRule($this);
        }
    }

    /**
     * @param  array<string, string>  $attributes
     *
     * @internal Public only because Filament calls this validation hook;
     * not covered by semver.
     */
    public function dehydrateValidationAttributes(array &$attributes): void
    {
        foreach ($this->getScanSteps() as $step) {
            $attributes[$this->getStepValuePath($step->getKey())] = $step->getLabel();
        }
    }

    /**
     * @param  array<string, mixed>  $messages
     *
     * @internal Public only because Filament calls this validation hook;
     * not covered by semver.
     */
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

    /**
     * Run the server checks for one step scan; called by the sequence JS, usable from tests.
     *
     * @return array<string, mixed>
     */
    #[ExposedLivewireMethod]
    public function handleStepScan(string $step, string $code, string $source): array
    {
        return $this->sequenceEngine()
            ->handleScan($step, $code, $source, $this->getSequenceState())
            ->toClient();
    }

    /**
     * Finish a waiting or open-ended repeat step (the Done control).
     *
     * @return array<string, mixed>
     *
     * @internal Public only because the sequence JS calls it through
     * #[ExposedLivewireMethod]; not covered by semver.
     */
    #[ExposedLivewireMethod]
    public function finishStep(string $step): array
    {
        return $this->sequenceEngine()
            ->finishStep($step, $this->getSequenceState())
            ->toClient();
    }

    /**
     * Remove one collected code from a repeat step (a mis-scan).
     *
     * @return array<string, mixed>
     *
     * @internal Public only because the sequence JS calls it through
     * #[ExposedLivewireMethod]; not covered by semver.
     */
    #[ExposedLivewireMethod]
    public function removeStepCode(string $step, int $index): array
    {
        return $this->sequenceEngine()
            ->removeStepCode($step, $index, $this->getSequenceState())
            ->toClient();
    }

    /**
     * Clear a step (the Clear control on sticky steps) and ask for it again.
     *
     * @return array<string, mixed>
     *
     * @internal Public only because the sequence JS calls it through
     * #[ExposedLivewireMethod]; not covered by semver.
     */
    #[ExposedLivewireMethod]
    public function clearStep(string $step): array
    {
        return $this->sequenceEngine()
            ->clearStep($step, $this->getSequenceState())
            ->toClient();
    }

    /**
     * A repeat step is filled with its count of codes, or one code when open-ended.
     *
     * @param  array<string, mixed>  $values
     */
    public function isStepFilled(ScanStep $step, array $values): bool
    {
        return $this->stepEvaluator()->isStepFilled($step, $values, $this->getRepeatCountOverride($step->getKey()));
    }

    /**
     * Resolved repeat(n) count for a step.
     *
     * @param  array<string, mixed>  $values
     */
    public function getRepeatLimit(ScanStep $step, array $values): ?int
    {
        return $this->stepEvaluator()->getRepeatLimit($step, $values, $this->getRepeatCountOverride($step->getKey()));
    }

    /** Resolved repeat-complete behaviour for a step. */
    public function getRepeatComplete(ScanStep $step): RepeatComplete
    {
        return $this->stepEvaluator()->getRepeatComplete(
            $step,
            function_exists('config') ? config('qr-code.sequence.repeat_complete') : null,
        );
    }

    /** Resolved finish code for a step. */
    public function getFinishCode(ScanStep $step): ?string
    {
        return $this->stepEvaluator()->getFinishCode(
            $step,
            function_exists('config') ? config('qr-code.sequence.finish_code') : null,
        );
    }

    /**
     * The code a step checks: the normalizeWith() presets, the sequence's
     * normalizeStepUsing(), then the step's own normalizeUsing().
     *
     * @param  array<string, mixed>  $values
     *
     * @internal Not covered by semver.
     */
    public function normalizeStepCode(ScanStep $step, string $code, array $values): string
    {
        return $this->stepEvaluator()->normalizeStepCode(
            $step,
            $code,
            $values,
            function (string $key, string $raw): string {
                $raw = $this->applyCodeNormalizer($raw);
                $normalized = $this->normalizeStepValue($key, $raw);

                return is_scalar($normalized) ? trim((string) $normalized) : $raw;
            },
        );
    }

    /** Provided by HasCodeNormalization. */
    abstract protected function applyCodeNormalizer(string $code): string;

    /**
     * The values a step saw when it was scanned: the active steps before it,
     * plus the codes of a repeat step scanned before the one at $position.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     *
     * @internal Not covered by semver.
     */
    public function valuesBeforeStep(ScanStep $step, array $values, ?int $position = null): array
    {
        return $this->stepEvaluator()->valuesBeforeStep($this->getScanSteps(), $step, $values, $position);
    }

    /**
     * @param  array<string, mixed>  $values
     *
     * @internal Not covered by semver.
     */
    public function checkStep(ScanStep $step, string $code, ?ScanInput $source, array $values): ScanResult
    {
        return $this->stepEvaluator()->checkStep(
            $step,
            $code,
            $source,
            $values,
            $this->isGs1() ? $this->getGs1ScanCheck() : null,
            $this,
        );
    }

    /**
     * The GS1 check for this step: the step's own gs1(), else the sequence default.
     *
     * @internal
     */
    public function gs1CheckFor(ScanStep $step): ?Gs1ScanCheck
    {
        return $this->stepEvaluator()->gs1CheckFor($step, $this->isGs1() ? $this->getGs1ScanCheck() : null);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{0: string, 1: string}|null
     *
     * @internal Not covered by semver.
     */
    public function findInvalidStep(array $values): ?array
    {
        return $this->sequenceEngine()->findInvalidStep($values);
    }

    /**
     * @param  array<string, mixed>  $values
     *
     * @internal Not covered by semver.
     */
    public function allRequiredFilled(array $values): bool
    {
        return $this->sequenceEngine()->allRequiredFilled($values);
    }

    /**
     * A step is active unless its when() condition returns false. A condition
     * that throws counts as active, so a broken closure never hides a step.
     *
     * @param  array<string, mixed>  $values
     */
    public function isStepActive(ScanStep $step, array $values): bool
    {
        return $this->stepEvaluator()->isStepActive($step, $values);
    }

    /**
     * Drop the values of inactive steps, deciding in step order so a step's
     * condition sees the values that remain before it.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     *
     * @internal Not covered by semver.
     */
    public function withoutInactiveSteps(array $values): array
    {
        return $this->sequenceEngine()->withoutInactiveSteps($values);
    }

    /**
     * Messages a failed submit left on the steps, in step order. The sequence
     * view lists them outside its wire:ignore'd scanning UI.
     *
     * @return list<array{step: string, label: string, message: string}>
     */
    public function getStepErrors(): array
    {
        try {
            $bag = $this->getLivewire()->getErrorBag();
        } catch (Throwable) {
            return [];
        }

        $errors = [];

        foreach ($this->getScanSteps() as $step) {
            $message = $bag->first($this->getStepValuePath($step->getKey()));

            if ($message !== '') {
                $errors[] = ['step' => $step->getKey(), 'label' => self::plainAnnouncementText($step->getLabel()), 'message' => $message];
            }
        }

        return $errors;
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
        $values = $this->sequenceEngine()->resetValues($this->getSequenceState(), $keepSticky, $values);

        $this->writeSequenceValues($values);
        $this->forgetStepErrors();

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

    /** Repeat count set at runtime with setRepeatCount(), if any. */
    public function getRepeatCountOverride(string $step): ?int
    {
        try {
            $count = session()->get($this->repeatOverrideSessionKey($step));
        } catch (Throwable) {
            return null;
        }

        return is_int($count) ? $count : null;
    }

    /** The key page events use to target this sequence: its schema key, else its state path. */
    public function getControlKey(): string
    {
        return (string) ($this->getKey() ?? $this->getComponentStatePath() ?? '');
    }

    protected function stepEvaluator(): StepEvaluator
    {
        $steps = $this->getScanSteps();

        return new StepEvaluator(
            evaluate: fn (Closure $closure, array $named = [], array $typed = []): mixed => $this->evaluate($closure, $named, $typed),
            evaluateStepClosure: fn (Closure $closure, ScanStep $step): mixed => $this->evaluateStepClosure($closure, $step),
            translate: fn (string $key, array $replace = []): string => (string) __($key, $replace),
            validateRules: function (array $values, array $rules, array $attributes) use ($steps): ?array {
                $validator = Validator::make($values, $rules, [], $attributes);

                if (! $validator->fails()) {
                    return null;
                }

                foreach ($steps as $scanStep) {
                    if (filled($message = $validator->errors()->first($scanStep->getKey()))) {
                        return [$scanStep->getKey(), $message];
                    }
                }

                $orderedSteps = array_values($steps);
                $lastStep = $orderedSteps === [] ? '' : $orderedSteps[count($orderedSteps) - 1]->getKey();

                return [$lastStep, (string) $validator->errors()->first()];
            },
            onWarning: function (string $message): void {
                if (app()->isLocal()) {
                    Log::warning($message);
                }
            },
        );
    }

    protected function sequenceEngine(): SequenceEngine
    {
        return new SequenceEngine(
            steps: $this->getScanSteps(),
            evaluator: $this->stepEvaluator(),
            defaultGs1: fn (): ?Gs1ScanCheck => $this->isGs1() ? $this->getGs1ScanCheck() : null,
            defaultKeepMissingGs1: fn (): bool => $this->shouldKeepMissingGs1Fields(),
            defaultRepeatComplete: function_exists('config') ? config('qr-code.sequence.repeat_complete') : null,
            defaultFinishCode: function_exists('config') ? config('qr-code.sequence.finish_code') : null,
            invalidBehavior: $this->getInvalidSequenceBehavior(),
            shouldResetAfterSubmit: $this->shouldResetAfterSubmit(),
            isContinuous: $this->isContinuous(),
            validateAllUsing: $this->validateAllUsing,
            repeatOverrideFor: fn (string $step): ?int => $this->getRepeatCountOverride($step),
            normalizeComponentCode: function (string $key, string $raw): string {
                $raw = $this->applyCodeNormalizer($raw);
                $normalized = $this->normalizeStepValue($key, $raw);

                return is_scalar($normalized) ? trim((string) $normalized) : $raw;
            },
            onWriteValues: function (array $values): void {
                $this->writeSequenceValues($values);
            },
            onFillGs1: function (ElementString $elements, Gs1ScanCheck $check, bool $keep): void {
                $this->fillGs1Fields($elements, $check, $keep);
            },
            onStepAccepted: function (ScanResult $result, string $step, array $values): void {
                $this->dispatchAcceptedStep($result, $step, $values);
            },
            onStepRejected: function (ScanResult $result, string $step, array $values): void {
                $this->rejectStep($result, $step, $values);
            },
            onSequenceCompleted: function (array $values): ?bool {
                $this->evaluate($this->onCompletedCallback, ['values' => $values]);

                [$livewireClass, $livewireId] = $this->livewireIdentity();
                event(new ScanSequenceCompleted($values, $this->getComponentStatePath(), $livewireClass, $livewireId));

                return $this->shouldAutoSubmit() ? $this->submitSequence() : null;
            },
            component: $this,
        );
    }

    protected function getLastStepKey(): string
    {
        $steps = array_values($this->getScanSteps());

        return $steps === [] ? '' : $steps[count($steps) - 1]->getKey();
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
        $this->forgetStepErrors([$step]);
        event(new ScanAccepted($result, $step, ...$this->stepEventContext($step, $values)));
        $this->runStepHook($this->onStepAcceptedCallback, $result, $step, $values);
        event(new QrCodeScanned(
            code: $result->code,
            source: ($result->source ?? ScanInput::Camera)->value,
            field: $step,
            context: ['record_key' => $result->recordKey()],
        ));
    }

    /** @param  list<string>|null  $steps  null forgets every step */
    protected function forgetStepErrors(?array $steps = null): void
    {
        $steps ??= array_map(fn (ScanStep $step): string => $step->getKey(), $this->getScanSteps());

        try {
            $livewire = $this->getLivewire();
        } catch (Throwable) {
            return;
        }

        foreach ($steps as $step) {
            $livewire->resetErrorBag($this->getStepValuePath($step));
        }
    }

    protected function repeatOverrideSessionKey(string $step): string
    {
        return 'filament-qr-code.repeat.'.$this->getLivewire()->getId().'.'.$this->getControlKey().'.'.$step;
    }

    protected function ensureStepExists(string $step): int
    {
        return $this->sequenceEngine()->findStepIndex($step)
            ?? throw new InvalidArgumentException("QrScanSequence has no step [{$step}].");
    }

    /**
     * Write values the way the browser always has: the component's own state
     * path plus the flat per-step path under the parent schema.
     *
     * @param  array<string, mixed>  $values
     */
    protected function writeSequenceValues(array $values): void
    {
        $livewire = $this->getLivewire();

        if (($statePath = $this->getComponentStatePath()) !== null) {
            data_set($livewire, $statePath, $values);
        }

        $prefix = $this->getFlatStatePrefix();

        foreach ($this->getScanSteps() as $step) {
            $key = $step->getKey();
            $path = $prefix !== '' ? "{$prefix}.{$key}" : $key;

            try {
                data_set($livewire, $path, $values[$key] ?? null);
            } catch (Throwable) {
                // The flat path may not exist on this Livewire component.
            }
        }
    }
}
