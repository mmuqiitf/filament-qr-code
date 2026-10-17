<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

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
