<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Throwable;

/**
 * The live-scan checks shared by QrScanner and sequence steps: rules, then a
 * lookup, then closures. The first failure stops the run.
 */
final readonly class ScanCheckPipeline
{
    /**
     * @param  array<int, mixed>  $rules
     * @param  array<int, Closure>  $closures
     * @param  class-string<Model>|null  $lookupModel
     */
    public function __construct(
        public array $rules = [],
        public array $closures = [],
        public ?string $lookupModel = null,
        public string $lookupColumn = 'code',
        public ?Closure $modifyLookupQueryUsing = null,
    ) {}

    public function hasChecks(): bool
    {
        return $this->rules !== [] || $this->closures !== [] || $this->lookupModel !== null;
    }

    /**
     * @param  Closure(Closure, array<string, mixed>, array<class-string, mixed>): mixed  $evaluate  the owning component's evaluate()
     * @param  array<string, mixed>  $context  extra named injections for closures (for example the step or the values so far)
     */
    public function run(string $code, ?ScanInput $source, Closure $evaluate, string $attributeLabel, array $context = []): ScanResult
    {
        try {
            return $this->runStages($code, $source, $evaluate, $attributeLabel, $context);
        } catch (ValidationException $exception) {
            return ScanResult::reject($code, $source, $exception->validator->errors()->first());
        } catch (Throwable $exception) {
            report($exception);

            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_failed'));
        }
    }

    /** @param  array<string, mixed>  $context */
    private function runStages(string $code, ?ScanInput $source, Closure $evaluate, string $attributeLabel, array $context): ScanResult
    {
        if ($code === '') {
            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_empty'));
        }

        if ($rejection = $this->checkRules($code, $source, $attributeLabel)) {
            return $rejection;
        }

        $record = $this->lookUp($code, $source, $evaluate);

        if ($record instanceof ScanResult) {
            return $record;
        }

        return $this->runClosures($code, $source, $record, $evaluate, $context)
            ?? ScanResult::accept($code, $source, $record);
    }

    private function checkRules(string $code, ?ScanInput $source, string $attributeLabel): ?ScanResult
    {
        if ($this->rules === []) {
            return null;
        }

        $validator = Validator::make(
            ['code' => $code],
            ['code' => $this->rules],
            attributes: ['code' => $attributeLabel],
        );

        if ($validator->passes()) {
            return null;
        }

        return ScanResult::reject($code, $source, (string) $validator->errors()->first('code'));
    }

    private function lookUp(string $code, ?ScanInput $source, Closure $evaluate): Model|ScanResult|null
    {
        if ($this->lookupModel === null) {
            return null;
        }

        $query = $this->lookupModel::query();

        if ($this->modifyLookupQueryUsing instanceof Closure) {
            $query = $evaluate($this->modifyLookupQueryUsing, ['query' => $query], [Builder::class => $query]) ?? $query;
        }

        $record = $query->where($this->lookupColumn, $code)->first();

        if ($record instanceof Model) {
            return $record;
        }

        return ScanResult::reject($code, $source, __('filament-qr-code::ui.record_not_found', ['code' => $code]));
    }

    /** @param  array<string, mixed>  $context */
    private function runClosures(string $code, ?ScanInput $source, ?Model $record, Closure $evaluate, array $context): ?ScanResult
    {
        foreach ($this->closures as $check) {
            $outcome = $evaluate($check, [
                ...$context,
                'code' => $code,
                'record' => $record,
                'source' => $source,
            ], [
                ScanInput::class => $source,
            ]);

            if ($outcome === false) {
                return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_rejected'));
            }

            if (is_string($outcome) && $outcome !== '') {
                return ScanResult::reject($code, $source, $outcome);
            }
        }

        return null;
    }
}
