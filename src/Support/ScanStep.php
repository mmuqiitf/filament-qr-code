<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Enums\RepeatComplete;

/**
 * One step of a QrScanSequence: a state key, a label and the checks a scanned
 * code must pass (rules, then a lookup, then closures).
 */
final class ScanStep
{
    private string|Closure|null $label = null;

    /** @var array<int, mixed> */
    private array $rules = [];

    /** @var array<int, Closure> */
    private array $closures = [];

    /** @var class-string<Model>|null */
    private ?string $lookupModel = null;

    private string $lookupColumn = 'code';

    private ?Closure $modifyLookupQueryUsing = null;

    private bool|Closure $isOptional = false;

    private bool|Closure $isSticky = false;

    private bool $isRepeating = false;

    private int|Closure|null $repeatTimes = null;

    private RepeatComplete|Closure|null $repeatComplete = null;

    private string|Closure|null $finishCode = null;

    private bool|Closure $allowsDuplicates = false;

    private ?Closure $activeWhen = null;

    private function __construct(private readonly string $key) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    /**
     * Adapter for the legacy fields() shapes: 'key', 'key' => 'Label' and ['key' => ..., 'label' => ...].
     */
    public static function fromField(int|string $index, mixed $field): ?self
    {
        if (is_string($field)) {
            $step = self::make(is_string($index) ? $index : $field);

            return $step->label(self::headline($field));
        }

        if (! is_array($field)) {
            return null;
        }

        $key = isset($field['key']) && is_string($field['key']) ? $field['key'] : (string) $index;
        $label = isset($field['label']) && is_string($field['label']) ? $field['label'] : null;

        return self::make($key)->label($label);
    }

    public function label(string|Closure|null $label): self
    {
        $this->label = $label;

        return $this;
    }

    /** @param  array<int, mixed>|ValidationRule|Closure  $rules */
    public function validateUsing(array|ValidationRule|Closure $rules): self
    {
        if ($rules instanceof Closure) {
            $this->closures[] = $rules;

            return $this;
        }

        $this->rules = [...$this->rules, ...(is_array($rules) ? $rules : [$rules])];

        return $this;
    }

    /** @param  class-string<Model>  $model */
    public function lookup(string $model, string $column = 'code', ?Closure $modifyQueryUsing = null): self
    {
        $this->lookupModel = $model;
        $this->lookupColumn = $column;
        $this->modifyLookupQueryUsing = $modifyQueryUsing;

        return $this;
    }

    public function optional(bool|Closure $condition = true): self
    {
        $this->isOptional = $condition;

        return $this;
    }

    /** Keep the value across resetAfterSubmit(), continuous() and Restart rounds. */
    public function sticky(bool|Closure $condition = true): self
    {
        $this->isSticky = $condition;

        return $this;
    }

    public function isSticky(): bool
    {
        return (bool) value($this->isSticky);
    }

    /**
     * Collect several codes into a list. With a count (or a closure receiving
     * $values) the step fills up; without one it stays open until finished.
     */
    public function repeat(int|Closure|null $times = null): self
    {
        $this->isRepeating = true;
        $this->repeatTimes = $times;

        return $this;
    }

    public function onRepeatComplete(RepeatComplete|Closure|null $behavior): self
    {
        $this->repeatComplete = $behavior;

        return $this;
    }

    /** A scan that finishes this repeat step instead of being stored. */
    public function finishWith(string|Closure|null $code): self
    {
        $this->finishCode = $code;

        return $this;
    }

    public function allowDuplicates(bool|Closure $condition = true): self
    {
        $this->allowsDuplicates = $condition;

        return $this;
    }

    /**
     * Make the step conditional. The sequence evaluates the closure with
     * $values and $step; while it returns false the step is skipped, not
     * required, and its value is dropped.
     */
    public function when(?Closure $condition): self
    {
        $this->activeWhen = $condition;

        return $this;
    }

    public function getWhenUsing(): ?Closure
    {
        return $this->activeWhen;
    }

    public function isRepeating(): bool
    {
        return $this->isRepeating;
    }

    public function getRepeatTimesUsing(): int|Closure|null
    {
        return $this->repeatTimes;
    }

    public function getRepeatCompleteUsing(): RepeatComplete|Closure|null
    {
        return $this->repeatComplete;
    }

    public function getFinishCodeUsing(): string|Closure|null
    {
        return $this->finishCode;
    }

    public function allowsDuplicates(): bool
    {
        return (bool) value($this->allowsDuplicates);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        $label = value($this->label);

        return is_string($label) && $label !== '' ? $label : self::headline($this->key);
    }

    public function isOptional(): bool
    {
        return (bool) value($this->isOptional);
    }

    public function hasChecks(): bool
    {
        return $this->rules !== [] || $this->closures !== [] || $this->lookupModel !== null;
    }

    public function checkPipeline(): ScanCheckPipeline
    {
        return new ScanCheckPipeline(
            rules: $this->rules,
            closures: $this->closures,
            lookupModel: $this->lookupModel,
            lookupColumn: $this->lookupColumn,
            modifyLookupQueryUsing: $this->modifyLookupQueryUsing,
        );
    }

    /** @return array{key: string, label: string} */
    public function toField(): array
    {
        return ['key' => $this->key, 'label' => $this->getLabel()];
    }

    private static function headline(string $value): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $value));
    }
}
