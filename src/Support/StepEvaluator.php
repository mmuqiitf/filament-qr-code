<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Closure;
use Countable;
use Mmuqiitf\FilamentQrCode\Enums\RepeatComplete;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;
use Mmuqiitf\FilamentQrCode\Gs1\SyntaxTable;
use ReflectionFunction;
use Throwable;

/**
 * Pure step-level evaluation, repeat counting, and GS1 check preparation for
 * scan sequences. Has no dependency on Filament or Livewire.
 *
 * @internal Not covered by semver; may change in any release.
 */
final class StepEvaluator
{
    /** @var array<string, string> */
    private const FALLBACK_MESSAGES = [
        'filament-qr-code::ui.scan_empty' => 'The scanned code is empty.',
        'filament-qr-code::ui.scan_rejected' => 'This code was rejected.',
        'filament-qr-code::ui.step_inactive' => 'This step does not apply right now.',
        'filament-qr-code::ui.repeat_full' => 'This step already has :count codes.',
        'filament-qr-code::ui.duplicate_in_step' => '":code" was already scanned in this step.',
        'filament-qr-code::ui.repeat_too_many' => 'This step has more than :count code(s). Remove the extra ones before finishing.',
        'filament-qr-code::ui.repeat_needs_codes' => 'Scan at least :count code(s) before finishing this step.',
        'filament-qr-code::ui.gs1_plain_barcode' => 'Plain product barcode, not a GS1 label',
        'filament-qr-code::ui.gs1_missing_ai' => 'Missing AI (:ai) :title',
        'filament-qr-code::ui.gs1_not_gs1' => 'Not a GS1 label',
    ];

    /**
     * @param  (Closure(Closure, array<string, mixed>, array<class-string, mixed>): mixed)|null  $evaluate
     * @param  (Closure(Closure, ScanStep): mixed)|null  $evaluateStepClosure
     * @param  (Closure(string, array<string, int|string>): string)|null  $translate
     * @param  (Closure(array<string, mixed>, array<string, mixed>, array<string, string>): ?array{0: string, 1: string})|null  $validateRules
     * @param  (Closure(string): void)|null  $onWarning
     */
    public function __construct(
        private readonly ?Closure $evaluate = null,
        private readonly ?Closure $evaluateStepClosure = null,
        private readonly ?Closure $translate = null,
        private readonly ?Closure $validateRules = null,
        private readonly ?Closure $onWarning = null,
    ) {}

    /**
     * @param  array<string, mixed>  $named
     * @param  array<class-string, mixed>  $typed
     */
    public function evaluateValue(mixed $value, array $named = [], array $typed = []): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }

        if ($this->evaluate instanceof Closure) {
            return ($this->evaluate)($value, $named, $typed);
        }

        return $this->invokeClosure($value, $named, $typed);
    }

    public function evaluateStepValue(mixed $value, ScanStep $step): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }

        if ($this->evaluateStepClosure instanceof Closure) {
            return ($this->evaluateStepClosure)($value, $step);
        }

        return $this->evaluateValue($value, ['step' => $step], [ScanStep::class => $step]);
    }

    /**
     * @param  array<string, int|string>  $replace
     */
    public function translate(string $key, array $replace = []): string
    {
        if ($this->translate instanceof Closure) {
            return (string) ($this->translate)($key, $replace);
        }

        if (function_exists('app') && function_exists('__') && app()->bound('translator')) {
            return (string) __($key, $replace);
        }

        $template = self::FALLBACK_MESSAGES[$key] ?? $key;
        $pairs = [];

        foreach ($replace as $name => $value) {
            $pairs[':'.$name] = (string) $value;
        }

        return strtr($template, $pairs);
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
            return (bool) $this->evaluateValue($condition, ['values' => $values, 'step' => $step]);
        } catch (Throwable $exception) {
            $this->warn("QrScanSequence step [{$step->getKey()}]: the when() condition failed ({$exception->getMessage()}); treating the step as active.");

            return true;
        }
    }

    /** @param  array<string, mixed>  $values */
    public function isRequiredStep(ScanStep $step, array $values): bool
    {
        return ! $step->isOptional() && $this->isStepActive($step, $values);
    }

    /**
     * Drop the values of inactive steps, deciding in step order so a step's
     * condition sees the values that remain before it.
     *
     * @param  array<int, ScanStep>  $steps
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function withoutInactiveSteps(array $steps, array $values): array
    {
        foreach ($steps as $step) {
            if (! $this->isStepActive($step, $values)) {
                unset($values[$step->getKey()]);
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return list<string>
     */
    public function repeatCodes(array $values, string $key): array
    {
        $codes = $values[$key] ?? [];

        if (is_string($codes)) {
            return $codes === '' ? [] : [$codes];
        }

        return is_array($codes) ? array_values(array_map(strval(...), array_filter($codes, is_scalar(...)))) : [];
    }

    /**
     * Resolved repeat(n) count for a step.
     *
     * @param  array<string, mixed>  $values
     */
    public function getRepeatLimit(ScanStep $step, array $values, ?int $override = null): ?int
    {
        if ($override !== null) {
            return $override;
        }

        try {
            $times = $this->evaluateValue($step->getRepeatTimesUsing(), ['values' => $values]);
        } catch (Throwable $exception) {
            $this->warn("QrScanSequence step [{$step->getKey()}]: the repeat() count closure failed ({$exception->getMessage()}); treating the step as open-ended until its values exist.");

            return null;
        }

        return is_numeric($times) ? max(1, (int) $times) : null;
    }

    /** Resolved repeat-complete behaviour for a step. */
    public function getRepeatComplete(ScanStep $step, mixed $default = null): RepeatComplete
    {
        if ($step->getRepeatTimesUsing() === null) {
            return RepeatComplete::Wait;
        }

        $behavior = $this->evaluateValue($step->getRepeatCompleteUsing()) ?? $default;

        if ($behavior instanceof RepeatComplete) {
            return $behavior;
        }

        return is_string($behavior) ? (RepeatComplete::tryFrom($behavior) ?? RepeatComplete::Advance) : RepeatComplete::Advance;
    }

    /** Resolved finish code for a step. */
    public function getFinishCode(ScanStep $step, mixed $default = null): ?string
    {
        $code = $this->evaluateValue($step->getFinishCodeUsing()) ?? $default;

        return is_string($code) && trim($code) !== '' ? trim($code) : null;
    }

    /**
     * A repeat step is filled with its count of codes, or one code when open-ended.
     *
     * @param  array<string, mixed>  $values
     */
    public function isStepFilled(ScanStep $step, array $values, ?int $repeatOverride = null): bool
    {
        if (! $step->isRepeating()) {
            return self::isFilled($values[$step->getKey()] ?? null);
        }

        $count = count($this->repeatCodes($values, $step->getKey()));

        return $count >= ($this->getRepeatLimit($step, $values, $repeatOverride) ?? 1);
    }

    /**
     * The code a step checks: component presets + step normalizer, then the step's own normalizeUsing().
     *
     * @param  array<string, mixed>  $values
     * @param  (Closure(string, string): string)|null  $normalizeForComponent
     */
    public function normalizeStepCode(ScanStep $step, string $code, array $values, ?Closure $normalizeForComponent = null): string
    {
        if ($normalizeForComponent instanceof Closure) {
            $code = $normalizeForComponent($step->getKey(), $code);
        }

        if (! ($normalizer = $step->getNormalizeUsing()) instanceof Closure) {
            return $code;
        }

        $normalized = $this->evaluateValue($normalizer, [
            'code' => $code,
            'rawValue' => $code,
            'state' => $code,
            'step' => $step,
            'values' => $values,
        ]);

        return is_scalar($normalized) ? trim((string) $normalized) : $code;
    }

    /**
     * The values a step saw when it was scanned: the active steps before it,
     * plus the codes of a repeat step scanned before the one at $position.
     *
     * @param  array<int, ScanStep>  $steps
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function valuesBeforeStep(array $steps, ScanStep $step, array $values, ?int $position = null): array
    {
        $values = $this->withoutInactiveSteps($steps, $values);
        $before = [];

        foreach ($steps as $candidate) {
            $key = $candidate->getKey();

            if ($key === $step->getKey()) {
                if ($position !== null && $position > 0 && is_array($values[$key] ?? null)) {
                    $before[$key] = array_slice(array_values($values[$key]), 0, $position);
                }

                break;
            }

            if (array_key_exists($key, $values)) {
                $before[$key] = $values[$key];
            }
        }

        return $before;
    }

    /**
     * The GS1 check for this step: the step's own gs1(), else the sequence default.
     */
    public function gs1CheckFor(ScanStep $step, ?Gs1ScanCheck $defaultGs1 = null): ?Gs1ScanCheck
    {
        if ($step->isGs1()) {
            $fill = $this->evaluateStepValue($step->getGs1Fill(), $step);
            $require = $this->evaluateStepValue($step->getGs1Require(), $step);

            return Gs1ScanCheck::from(is_array($fill) ? $fill : [], is_array($require) ? $require : []);
        }

        return $defaultGs1;
    }

    /**
     * The bracketed element string, or a rejection. Unchanged when the step is not GS1.
     */
    public function prepareStepGs1(ScanStep $step, string $code, ?ScanInput $source, ?Gs1ScanCheck $defaultGs1 = null): ScanResult|string
    {
        $check = $this->gs1CheckFor($step, $defaultGs1);

        if ($check === null) {
            return $code;
        }

        if ($this->hasTranslator()) {
            $elements = $check->inspect($code);

            return is_string($elements)
                ? ScanResult::reject($code, $source, $elements)
                : $elements->bracketed();
        }

        return $this->inspectGs1Standalone($check, $code, $source);
    }

    /**
     * Resolve the GS1 field-fill plan for an accepted bracketed label.
     *
     * @return array{elements: ElementString, check: Gs1ScanCheck, keepMissing: bool}|null
     */
    public function gs1FillPlan(
        ScanStep $step,
        string $bracketed,
        ?Gs1ScanCheck $defaultGs1 = null,
        bool $defaultKeepMissing = false,
    ): ?array {
        $check = $this->gs1CheckFor($step, $defaultGs1);

        if ($check === null || $check->fill === []) {
            return null;
        }

        $keepMissing = $step->isGs1()
            ? (bool) $this->evaluateStepValue($step->getGs1KeepMissing(), $step)
            : $defaultKeepMissing;

        return [
            'elements' => Gs1::parse($bracketed),
            'check' => $check,
            'keepMissing' => $keepMissing,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function checkStep(
        ScanStep $step,
        string $code,
        ?ScanInput $source,
        array $values,
        ?Gs1ScanCheck $defaultGs1 = null,
        ?object $component = null,
    ): ScanResult {
        $prepared = $this->prepareStepGs1($step, $code, $source, $defaultGs1);

        if ($prepared instanceof ScanResult) {
            return $prepared;
        }

        if ($prepared === '' && ! $this->hasTranslator()) {
            return ScanResult::reject($prepared, $source, $this->translate('filament-qr-code::ui.scan_empty'));
        }

        return $step->checkPipeline($component)->run(
            $prepared,
            $source,
            fn (Closure $closure, array $named = [], array $typed = []): mixed => $this->evaluateValue($closure, $named, $typed),
            $step->getLabel(),
            ['step' => $step, 'values' => $values],
        );
    }

    /**
     * The repeat step's own rules (full, duplicate) then its checks, without side effects.
     *
     * @param  array<string, mixed>  $values
     */
    public function checkRepeatScan(
        ScanStep $step,
        string $code,
        ScanInput $input,
        array $values,
        ?int $repeatOverride = null,
        ?Gs1ScanCheck $defaultGs1 = null,
        ?object $component = null,
    ): ScanResult {
        $codes = $this->repeatCodes($values, $step->getKey());
        $limit = $this->getRepeatLimit($step, $values, $repeatOverride);

        return match (true) {
            $limit !== null && count($codes) >= $limit => ScanResult::reject($code, $input, $this->translate('filament-qr-code::ui.repeat_full', ['count' => $limit])),
            ! $step->allowsDuplicates() && in_array($code, $codes, true) => ScanResult::reject($code, $input, $this->translate('filament-qr-code::ui.duplicate_in_step', ['code' => $code])),
            default => $this->checkStep($step, $code, $input, $values, $defaultGs1, $component),
        };
    }

    /**
     * @param  array<int, ScanStep>  $steps
     * @param  array<string, mixed>  $values
     * @param  array<int|string, mixed>|Closure|null  $validateAllUsing
     * @return array{0: string, 1: string}|null
     */
    public function findInvalidStep(array $steps, array $values, array|Closure|null $validateAllUsing): ?array
    {
        if ($validateAllUsing === null) {
            return null;
        }

        $orderedSteps = array_values($steps);
        $lastStep = $orderedSteps === [] ? '' : $orderedSteps[count($orderedSteps) - 1]->getKey();

        if ($validateAllUsing instanceof Closure) {
            $failures = $this->evaluateValue($validateAllUsing, ['values' => $values]);

            if (is_string($failures) && $failures !== '') {
                return [$lastStep, $failures];
            }

            if (! is_array($failures)) {
                return null;
            }

            $knownKeys = array_flip(array_map(fn (ScanStep $step): string => $step->getKey(), $orderedSteps));

            foreach ($failures as $key => $message) {
                if (self::isFilled($message)) {
                    $resolvedKey = is_string($key) && isset($knownKeys[$key]) ? $key : $lastStep;

                    return [$resolvedKey, is_scalar($message) ? (string) $message : $this->translate('filament-qr-code::ui.scan_rejected')];
                }
            }

            return null;
        }

        $rules = [];

        foreach ($validateAllUsing as $key => $rule) {
            if (is_int($key)) {
                $rules[$lastStep][] = $rule;

                continue;
            }

            $rules[$key] = array_merge($rules[$key] ?? [], is_string($rule) ? explode('|', $rule) : (array) $rule);
        }

        $attributes = [];

        foreach ($orderedSteps as $scanStep) {
            $attributes[$scanStep->getKey()] = $scanStep->getLabel();
        }

        if ($this->validateRules instanceof Closure) {
            return ($this->validateRules)($values, $rules, $attributes);
        }

        return null;
    }

    public static function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }

    public static function isFilled(mixed $value): bool
    {
        return ! self::isBlank($value);
    }

    private function warn(string $message): void
    {
        if ($this->onWarning instanceof Closure) {
            ($this->onWarning)($message);
        }
    }

    private function hasTranslator(): bool
    {
        return function_exists('app') && app()->bound('translator');
    }

    private function inspectGs1Standalone(Gs1ScanCheck $check, string $code, ?ScanInput $source): ScanResult|string
    {
        if (Gs1ScanCheck::isPlainBarcode($code)) {
            return ScanResult::reject($code, $source, $this->translate('filament-qr-code::ui.gs1_plain_barcode'));
        }

        try {
            $elements = Gs1::parse($code);
        } catch (InvalidGs1Exception $exception) {
            $ai = $exception->ai;
            $message = $ai === null
                ? $this->translate('filament-qr-code::ui.gs1_not_gs1')
                : "GS1 invalid ({$exception->kind}) for AI ({$ai}).";

            return ScanResult::reject($code, $source, $message);
        }

        $present = $elements->toArray();

        foreach ($check->require as $ai) {
            if (! array_key_exists($ai, $present)) {
                return ScanResult::reject($code, $source, $this->translate('filament-qr-code::ui.gs1_missing_ai', [
                    'ai' => $ai,
                    'title' => SyntaxTable::find($ai)->title ?? '',
                ]));
            }
        }

        return $elements->bracketed();
    }

    /**
     * @param  array<string, mixed>  $named
     * @param  array<class-string, mixed>  $typed
     */
    private function invokeClosure(Closure $closure, array $named, array $typed): mixed
    {
        $reflection = new ReflectionFunction($closure);
        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $named)) {
                $args[] = $named[$name];

                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof \ReflectionNamedType && ! $type->isBuiltin() && array_key_exists($type->getName(), $typed)) {
                $args[] = $typed[$type->getName()];

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();

                continue;
            }

            $args[] = null;
        }

        return $closure(...$args);
    }
}
