<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Closure;
use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Enums\RepeatComplete;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;

/**
 * Pure state-machine engine for QrScanSequence: step progression, optional
 * skipping, repeat steps, GS1 field plans, state merging, and whole-set
 * completion. Has no dependency on Filament or Livewire.
 *
 * @internal Not covered by semver; may change in any release.
 */
final class SequenceEngine
{
    /**
     * @param  array<int, ScanStep>  $steps
     * @param  array<int|string, mixed>|Closure|null  $validateAllUsing
     * @param  (Closure(string): ?int)|null  $repeatOverrideFor
     * @param  (Closure(string, string): string)|null  $normalizeComponentCode
     * @param  (Closure(array<string, mixed>): void)|null  $onWriteValues
     * @param  (Closure(ElementString, Gs1ScanCheck, bool): void)|null  $onFillGs1
     * @param  (Closure(ScanResult, string, array<string, mixed>): void)|null  $onStepAccepted
     * @param  (Closure(ScanResult, string, array<string, mixed>): void)|null  $onStepRejected
     * @param  (Closure(array<string, mixed>): ?bool)|null  $onSequenceCompleted
     */
    public function __construct(
        private readonly array $steps,
        private readonly StepEvaluator $evaluator = new StepEvaluator,
        private readonly Gs1ScanCheck|Closure|null $defaultGs1 = null,
        private readonly bool|Closure $defaultKeepMissingGs1 = false,
        private readonly mixed $defaultRepeatComplete = null,
        private readonly mixed $defaultFinishCode = null,
        private readonly InvalidSequenceBehavior $invalidBehavior = InvalidSequenceBehavior::JumpToStep,
        private readonly bool $shouldResetAfterSubmit = false,
        private readonly bool $isContinuous = false,
        private readonly array|Closure|null $validateAllUsing = null,
        private readonly ?Closure $repeatOverrideFor = null,
        private readonly ?Closure $normalizeComponentCode = null,
        private readonly ?Closure $onWriteValues = null,
        private readonly ?Closure $onFillGs1 = null,
        private readonly ?Closure $onStepAccepted = null,
        private readonly ?Closure $onStepRejected = null,
        private readonly ?Closure $onSequenceCompleted = null,
        private readonly ?object $component = null,
    ) {}

    public function evaluator(): StepEvaluator
    {
        return $this->evaluator;
    }

    /**
     * @param  array<string, mixed>  $currentState
     */
    public function handleScan(string $step, string $code, string|ScanInput $source, array $currentState): SequenceScanResult
    {
        $input = $source instanceof ScanInput ? $source : ScanInput::fromClient($source);
        $values = $this->withoutInactiveSteps($currentState);
        $index = $this->findStepIndex($step);

        if ($index === null) {
            return new SequenceScanResult(
                scan: ScanResult::reject(trim($code), $input, $this->evaluator->translate('filament-qr-code::ui.scan_rejected')),
                step: null,
                nextStep: null,
                values: $values,
            );
        }

        if (! $this->evaluator->isStepActive($this->steps[$index], $values)) {
            $result = ScanResult::reject(trim($code), $input, $this->evaluator->translate('filament-qr-code::ui.step_inactive'));
            $this->rejectStep($result, $step, $values);

            return new SequenceScanResult(
                scan: $result,
                step: $step,
                nextStep: $this->getFirstEmptyStepKey($values),
                values: $values,
            );
        }

        $code = $this->evaluator->normalizeStepCode($this->steps[$index], $code, $values, $this->normalizeComponentCode);

        if ($this->steps[$index]->isRepeating()) {
            return $this->handleRepeatScan($index, $code, $input, $values);
        }

        $result = $this->evaluator->checkStep(
            $this->steps[$index],
            $code,
            $input,
            $values,
            $this->resolveDefaultGs1(),
            $this->component,
        );
        $skipped = [];

        if (! $result->passes && $input !== ScanInput::Manual && $this->steps[$index]->isOptional()) {
            $landed = $this->landPastOptionalSteps($index, $code, $input, $values);

            if ($landed !== null && $this->steps[$landed['index']]->isRepeating()) {
                foreach ($landed['skipped'] as $skippedKey) {
                    unset($values[$skippedKey]);
                }

                return $this->acceptRepeatScan($landed['index'], $landed['result'], $values, $landed['skipped']);
            }

            if ($landed !== null) {
                $skipped = $landed['skipped'];
                $result = $landed['result'];
                $index = $landed['index'];
                $step = $this->steps[$index]->getKey();
            }
        }

        if (! $result->passes) {
            $this->rejectStep($result, $step, $values);

            return new SequenceScanResult(scan: $result, step: $step, nextStep: $step, values: $values);
        }

        foreach ($skipped as $skippedKey) {
            unset($values[$skippedKey]);
        }

        $values[$step] = $result->code;
        $this->writeValues($values);
        $this->fillGs1ForStep($this->steps[$index], $result->code);
        $this->acceptStep($result, $step, $values);

        $nextStep = $this->findNextStep($index, $values);

        if ($nextStep !== null) {
            return new SequenceScanResult(scan: $result, step: $step, nextStep: $nextStep, values: $values, skipped: $skipped);
        }

        return $this->completeSequence($result, $step, $values, $skipped);
    }

    /**
     * @param  array<string, mixed>  $currentState
     */
    public function finishStep(string $step, array $currentState): SequenceScanResult
    {
        $values = $this->withoutInactiveSteps($currentState);
        $index = $this->findStepIndex($step);

        if ($index === null || ! $this->steps[$index]->isRepeating()) {
            return new SequenceScanResult(
                scan: ScanResult::reject('', ScanInput::Manual, $this->evaluator->translate('filament-qr-code::ui.scan_rejected')),
                step: $index === null ? null : $step,
                nextStep: $index === null ? null : $step,
                values: $values,
            );
        }

        return $this->finishRepeatStep($index, '', ScanInput::Manual, $values);
    }

    /**
     * @param  array<string, mixed>  $currentState
     */
    public function removeStepCode(string $step, int $index, array $currentState): SequenceScanResult
    {
        $stepIndex = $this->findStepIndex($step);

        if ($stepIndex === null || ! $this->steps[$stepIndex]->isRepeating()) {
            return $this->rejectStepAction($step, $currentState);
        }

        $codes = $this->evaluator->repeatCodes($currentState, $step);
        $removed = $codes[$index] ?? '';
        unset($codes[$index]);
        $currentState[$step] = array_values($codes);

        if ($currentState[$step] === []) {
            unset($currentState[$step]);
        }

        $this->writeValues($currentState);

        return new SequenceScanResult(scan: ScanResult::accept($removed, ScanInput::Manual), step: $step, nextStep: $step, values: $currentState);
    }

    /**
     * @param  array<string, mixed>  $currentState
     */
    public function clearStep(string $step, array $currentState): SequenceScanResult
    {
        if ($this->findStepIndex($step) === null) {
            return $this->rejectStepAction($step, $currentState);
        }

        unset($currentState[$step]);
        $this->writeValues($currentState);

        return new SequenceScanResult(scan: ScanResult::accept('', ScanInput::Manual), step: $step, nextStep: $step, values: $currentState);
    }

    /**
     * @param  array<string, mixed>  $currentState
     * @param  array<string, mixed>  $seedValues
     * @return array<string, mixed>
     */
    public function resetValues(array $currentState, bool $keepSticky = true, array $seedValues = []): array
    {
        $kept = $keepSticky ? $this->stickyValues($currentState) : [];

        return [...$kept, ...$seedValues];
    }

    /**
     * Merge non-blank sequence values into form state without overwriting
     * values the form already holds.
     *
     * @param  array<string, mixed>  $formState
     * @param  array<string, mixed>  $sequenceState
     * @return array<string, mixed>
     */
    public function mergeState(array $formState, array $sequenceState): array
    {
        foreach ($sequenceState as $key => $value) {
            if (! array_key_exists($key, $formState) || StepEvaluator::isBlank($formState[$key])) {
                $formState[$key] = $value;
            }
        }

        return $formState;
    }

    /**
     * Active step keys that still lack a complete value in merged state.
     *
     * @param  array<string, mixed>  $mergedState
     * @return list<string>
     */
    public function missingKeys(array $mergedState): array
    {
        $missing = [];

        foreach ($this->steps as $step) {
            if (! $this->evaluator->isStepActive($step, $mergedState)) {
                continue;
            }

            $key = $step->getKey();

            if (
                StepEvaluator::isBlank($mergedState[$key] ?? null)
                || ($step->isRepeating() && ! $this->evaluator->isStepFilled($step, $mergedState, $this->repeatOverride($key)))
            ) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /** @param  array<string, mixed>  $values */
    public function allRequiredFilled(array $values): bool
    {
        foreach ($this->steps as $step) {
            if (
                $this->evaluator->isRequiredStep($step, $values)
                && ! $this->evaluator->isStepFilled($step, $values, $this->repeatOverride($step->getKey()))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function withoutInactiveSteps(array $values): array
    {
        return $this->evaluator->withoutInactiveSteps($this->steps, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function stickyValues(array $values): array
    {
        $kept = [];

        foreach ($this->steps as $step) {
            if ($step->isSticky() && StepEvaluator::isFilled($values[$step->getKey()] ?? null)) {
                $kept[$step->getKey()] = $values[$step->getKey()];
            }
        }

        return $kept;
    }

    public function findStepIndex(string $key): ?int
    {
        foreach ($this->steps as $index => $step) {
            if ($step->getKey() === $key) {
                return $index;
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $values */
    public function getFirstEmptyStepKey(array $values): ?string
    {
        foreach ($this->steps as $step) {
            if (
                $this->evaluator->isStepActive($step, $values)
                && ! $this->evaluator->isStepFilled($step, $values, $this->repeatOverride($step->getKey()))
            ) {
                return $step->getKey();
            }
        }

        return $this->getFirstStepKey();
    }

    public function getFirstStepKey(): ?string
    {
        return isset($this->steps[0]) ? $this->steps[0]->getKey() : null;
    }

    public function getLastStepKey(): string
    {
        $steps = array_values($this->steps);

        return $steps === [] ? '' : $steps[count($steps) - 1]->getKey();
    }

    /**
     * The first empty step after $index; past the end, the first empty
     * required step. Null when every required step has a value.
     *
     * @param  array<string, mixed>  $values
     */
    public function findNextStep(int $index, array $values): ?string
    {
        foreach (array_slice($this->steps, $index + 1) as $step) {
            if (
                $this->evaluator->isStepActive($step, $values)
                && ! $this->evaluator->isStepFilled($step, $values, $this->repeatOverride($step->getKey()))
            ) {
                return $step->getKey();
            }
        }

        foreach ($this->steps as $step) {
            if (
                $this->evaluator->isRequiredStep($step, $values)
                && ! $this->evaluator->isStepFilled($step, $values, $this->repeatOverride($step->getKey()))
            ) {
                return $step->getKey();
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{0: string, 1: string}|null
     */
    public function findInvalidStep(array $values): ?array
    {
        return $this->evaluator->findInvalidStep($this->steps, $values, $this->validateAllUsing);
    }

    /** @param  array<string, mixed>  $values */
    private function rejectStepAction(string $step, array $values): SequenceScanResult
    {
        $known = $this->findStepIndex($step) !== null;

        return new SequenceScanResult(
            scan: ScanResult::reject('', ScanInput::Manual, $this->evaluator->translate('filament-qr-code::ui.scan_rejected')),
            step: $known ? $step : null,
            nextStep: $known ? $step : null,
            values: $values,
        );
    }

    /** @param  array<string, mixed>  $values */
    private function handleRepeatScan(int $index, string $code, ScanInput $input, array $values): SequenceScanResult
    {
        $step = $this->steps[$index];
        $key = $step->getKey();

        if ($code !== '' && $code === $this->evaluator->getFinishCode($step, $this->defaultFinishCode)) {
            return $this->finishRepeatStep($index, $code, $input, $values);
        }

        $result = $this->evaluator->checkRepeatScan(
            $step,
            $code,
            $input,
            $values,
            $this->repeatOverride($key),
            $this->resolveDefaultGs1(),
            $this->component,
        );

        if (! $result->passes) {
            $this->rejectStep($result, $key, $values);

            return new SequenceScanResult(scan: $result, step: $key, nextStep: $key, values: $values);
        }

        return $this->acceptRepeatScan($index, $result, $values);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $skipped
     */
    private function acceptRepeatScan(int $index, ScanResult $result, array $values, array $skipped = []): SequenceScanResult
    {
        $step = $this->steps[$index];
        $key = $step->getKey();

        $values[$key] = [...$this->evaluator->repeatCodes($values, $key), $result->code];
        $this->writeValues($values);
        $this->fillGs1ForStep($step, $result->code);
        $this->acceptStep($result, $key, $values);

        $limit = $this->evaluator->getRepeatLimit($step, $values, $this->repeatOverride($key));
        $isFull = $limit !== null && count($values[$key]) >= $limit;

        if (! $isFull || $this->evaluator->getRepeatComplete($step, $this->defaultRepeatComplete) === RepeatComplete::Wait) {
            return new SequenceScanResult(scan: $result, step: $key, nextStep: $key, values: $values, skipped: $skipped);
        }

        return $this->advanceFrom($index, $result, $key, $values, $skipped);
    }

    /** @param  array<string, mixed>  $values */
    private function finishRepeatStep(int $index, string $code, ScanInput $input, array $values): SequenceScanResult
    {
        $step = $this->steps[$index];
        $key = $step->getKey();
        $limit = $this->evaluator->getRepeatLimit($step, $values, $this->repeatOverride($key));
        $needed = $limit ?? 1;
        $count = count($this->evaluator->repeatCodes($values, $key));

        if ($limit !== null && $count > $limit) {
            $result = ScanResult::reject($code, $input, $this->evaluator->translate('filament-qr-code::ui.repeat_too_many', ['count' => $limit]));
            $this->rejectStep($result, $key, $values);

            return new SequenceScanResult(scan: $result, step: $key, nextStep: $key, values: $values);
        }

        if ($count < $needed && ! ($step->isOptional() && $count === 0)) {
            if ($limit !== null && ($failure = $this->findInvalidStep($values)) !== null) {
                return $this->rejectInvalidSet(ScanResult::accept($code, $input), $key, $values, $failure);
            }

            $result = ScanResult::reject($code, $input, $this->evaluator->translate('filament-qr-code::ui.repeat_needs_codes', ['count' => $needed]));
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
    private function advanceFrom(int $index, ScanResult $result, string $key, array $values, array $skipped = []): SequenceScanResult
    {
        $nextStep = $this->findNextStep($index, $values);

        if ($nextStep !== null) {
            return new SequenceScanResult(scan: $result, step: $key, nextStep: $nextStep, values: $values, skipped: $skipped);
        }

        return $this->completeSequence($result, $key, $values, $skipped);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array{index: int, result: ScanResult, skipped: list<string>}|null
     */
    private function landPastOptionalSteps(int $index, string $code, ScanInput $input, array $values): ?array
    {
        $skipped = [$this->steps[$index]->getKey()];
        $walkIndex = $index;
        $defaultGs1 = $this->resolveDefaultGs1();

        while (true) {
            $nextIndex = $this->findNextActiveIndex($walkIndex, $values);

            if ($nextIndex === null) {
                return null;
            }

            $nextStep = $this->steps[$nextIndex];
            $nextResult = $nextStep->isRepeating()
                ? $this->evaluator->checkRepeatScan($nextStep, $code, $input, $values, $this->repeatOverride($nextStep->getKey()), $defaultGs1, $this->component)
                : $this->evaluator->checkStep($nextStep, $code, $input, $values, $defaultGs1, $this->component);

            if ($nextResult->passes) {
                return ['index' => $nextIndex, 'result' => $nextResult, 'skipped' => $skipped];
            }

            if (! $nextStep->isOptional()) {
                return null;
            }

            $skipped[] = $nextStep->getKey();
            $walkIndex = $nextIndex;
        }
    }

    /** @param  array<string, mixed>  $values */
    private function findNextActiveIndex(int $index, array $values): ?int
    {
        foreach (array_slice($this->steps, $index + 1, preserve_keys: true) as $nextIndex => $step) {
            if ($this->evaluator->isStepActive($step, $values)) {
                return $nextIndex;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $skipped
     */
    private function completeSequence(ScanResult $result, string $step, array $values, array $skipped = []): SequenceScanResult
    {
        if (($failure = $this->findInvalidStep($values)) !== null) {
            return $this->rejectInvalidSet($result, $step, $values, $failure, $skipped);
        }

        $submitted = $this->onSequenceCompleted instanceof Closure
            ? ($this->onSequenceCompleted)($values)
            : null;

        $reset = ($submitted === true && $this->shouldResetAfterSubmit)
            || ($submitted !== false && $this->isContinuous);

        if ($reset) {
            $values = $this->stickyValues($values);
            $this->writeValues($values);
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
     * @param  array<string, mixed>  $values
     * @param  array{0: string, 1: string}  $failure
     * @param  list<string>  $skipped
     */
    private function rejectInvalidSet(ScanResult $result, string $step, array $values, array $failure, array $skipped = []): SequenceScanResult
    {
        [$invalidStep, $message] = $failure;
        $restart = $this->invalidBehavior === InvalidSequenceBehavior::Restart;

        if ($restart) {
            $values = $this->stickyValues($values);
            $this->writeValues($values);
        } elseif ($invalidStep === $step && $result->code !== '' && $this->isRepeatingStep($step)) {
            $codes = $this->evaluator->repeatCodes($values, $step);
            $position = array_search($result->code, array_reverse($codes, true), true);

            if ($position !== false) {
                unset($codes[$position]);
                $values[$step] = array_values($codes);
                $this->writeValues($values);
            }
        }

        $rejected = ScanResult::reject($result->code, $result->source, $message);
        $this->rejectStep($rejected, $invalidStep, $values);

        return new SequenceScanResult(
            scan: $rejected,
            step: $step,
            nextStep: $restart ? $this->getFirstEmptyStepKey($values) : $invalidStep,
            values: $values,
            skipped: $skipped,
            invalidStep: $invalidStep,
            reset: $restart,
        );
    }

    private function isRepeatingStep(string $step): bool
    {
        $index = $this->findStepIndex($step);

        return $index !== null && $this->steps[$index]->isRepeating();
    }

    private function repeatOverride(string $step): ?int
    {
        return $this->repeatOverrideFor instanceof Closure ? ($this->repeatOverrideFor)($step) : null;
    }

    private function resolveDefaultGs1(): ?Gs1ScanCheck
    {
        return $this->defaultGs1 instanceof Closure ? ($this->defaultGs1)() : $this->defaultGs1;
    }

    private function resolveDefaultKeepMissingGs1(): bool
    {
        return $this->defaultKeepMissingGs1 instanceof Closure
            ? (bool) ($this->defaultKeepMissingGs1)()
            : $this->defaultKeepMissingGs1;
    }

    /** @param  array<string, mixed>  $values */
    private function writeValues(array $values): void
    {
        if ($this->onWriteValues instanceof Closure) {
            ($this->onWriteValues)($values);
        }
    }

    private function fillGs1ForStep(ScanStep $step, string $bracketed): void
    {
        if (! $this->onFillGs1 instanceof Closure) {
            return;
        }

        $plan = $this->evaluator->gs1FillPlan(
            $step,
            $bracketed,
            $this->resolveDefaultGs1(),
            $this->resolveDefaultKeepMissingGs1(),
        );

        if ($plan !== null) {
            ($this->onFillGs1)($plan['elements'], $plan['check'], $plan['keepMissing']);
        }
    }

    /** @param  array<string, mixed>  $values */
    private function acceptStep(ScanResult $result, string $step, array $values): void
    {
        if ($this->onStepAccepted instanceof Closure) {
            ($this->onStepAccepted)($result, $step, $values);
        }
    }

    /** @param  array<string, mixed>  $values */
    private function rejectStep(ScanResult $result, string $step, array $values): void
    {
        if ($this->onStepRejected instanceof Closure) {
            ($this->onStepRejected)($result, $step, $values);
        }
    }
}
