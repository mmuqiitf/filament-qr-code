<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Throwable;

/**
 * The live-scan checks shared by QrScanner and sequence steps: rules, then a
 * lookup (model and/or resolver), then closures, then piped stages. The first
 * failure stops the run.
 *
 * @internal Not covered by semver; may change in any release.
 */
final readonly class ScanCheckPipeline
{
    /**
     * @param  array<int, mixed>  $rules
     * @param  array<int, Closure>  $closures
     * @param  class-string<Model>|null  $lookupModel
     * @param  array<int, ScanCheckStage|class-string<ScanCheckStage>>  $stages
     */
    public function __construct(
        public array $rules = [],
        public array $closures = [],
        public ?string $lookupModel = null,
        public string $lookupColumn = 'code',
        public ?Closure $modifyLookupQueryUsing = null,
        public ?Closure $lookupResolver = null,
        public bool $lookupFallback = false,
        public array $stages = [],
        public ?object $component = null,
    ) {}

    public function hasChecks(): bool
    {
        return $this->rules !== []
            || $this->closures !== []
            || $this->lookupModel !== null
            || $this->lookupResolver !== null
            || $this->stages !== [];
    }

    /**
     * Validate a stage when it's added, so a typo fails at boot, not on a scan.
     *
     * @return ScanCheckStage|class-string<ScanCheckStage>
     */
    public static function ensureStage(mixed $stage): ScanCheckStage|string
    {
        if ($stage instanceof ScanCheckStage) {
            return $stage;
        }

        if (is_string($stage) && is_subclass_of($stage, ScanCheckStage::class)) {
            return $stage;
        }

        throw new InvalidArgumentException('A scan check stage must implement '.ScanCheckStage::class.'.');
    }

    /**
     * @param  Closure(Closure, array<string, mixed>, array<class-string, mixed>): mixed  $evaluate  the owning component's evaluate()
     * @param  array<string, mixed>  $context  extra named injections for closures (for example the step or the values so far)
     */
    public function run(string $code, ?ScanInput $source, Closure $evaluate, string $attributeLabel, array $context = []): ScanResult
    {
        try {
            return $this->runChecks($code, $source, $evaluate, $attributeLabel, $context);
        } catch (ValidationException $exception) {
            return ScanResult::reject($code, $source, $exception->validator->errors()->first());
        } catch (Throwable $exception) {
            report($exception);

            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_failed'));
        }
    }

    /** @param  array<string, mixed>  $context */
    private function runChecks(string $code, ?ScanInput $source, Closure $evaluate, string $attributeLabel, array $context): ScanResult
    {
        if ($code === '') {
            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_empty'));
        }

        if ($rejection = $this->checkRules($code, $source, $attributeLabel)) {
            return $rejection;
        }

        $record = $this->lookUp($code, $source, $evaluate, $context);

        if ($record instanceof ScanResult) {
            return $record;
        }

        return $this->runClosures($code, $source, $record, $evaluate, $context)
            ?? $this->runPipedStages($code, $source, $record, $context)
            ?? ScanResult::accept($code, $source, $record);
    }

    /** @param  array<string, mixed>  $context */
    private function runPipedStages(string $code, ?ScanInput $source, mixed $record, array $context): ?ScanResult
    {
        if ($this->stages === []) {
            return null;
        }

        $stageContext = new ScanCheckContext(
            code: $code,
            source: $source,
            record: $record,
            component: $this->component,
            step: ($context['step'] ?? null) instanceof ScanStep ? $context['step'] : null,
            values: is_array($context['values'] ?? null) ? $context['values'] : [],
        );

        foreach ($this->stages as $stage) {
            $instance = $stage instanceof ScanCheckStage ? $stage : app($stage);
            $outcome = $instance->check($stageContext);

            if ($outcome === false) {
                return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_rejected'));
            }

            if (is_string($outcome) && $outcome !== '') {
                return ScanResult::reject($code, $source, $outcome);
            }
        }

        return null;
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

    /** @param  array<string, mixed>  $context */
    private function lookUp(string $code, ?ScanInput $source, Closure $evaluate, array $context): mixed
    {
        if ($this->lookupModel === null && $this->lookupResolver === null) {
            return null;
        }

        // A resolver replaces the model lookup unless it's a fallback.
        $record = $this->lookupModel !== null && ($this->lookupResolver === null || $this->lookupFallback)
            ? $this->lookUpModel($code, $evaluate)
            : null;

        if ($record === null && $this->lookupResolver !== null) {
            $resolved = $evaluate($this->lookupResolver, [...$context, 'code' => $code, 'source' => $source], [ScanInput::class => $source]);
            $record = $resolved === false ? null : $resolved;
        }

        return $record ?? ScanResult::reject($code, $source, __('filament-qr-code::ui.record_not_found', ['code' => $code]));
    }

    private function lookUpModel(string $code, Closure $evaluate): ?Model
    {
        if ($this->lookupModel === null) {
            return null;
        }

        $query = $this->lookupModel::query();

        if ($this->modifyLookupQueryUsing instanceof Closure) {
            $query = $evaluate($this->modifyLookupQueryUsing, ['query' => $query], [Builder::class => $query]) ?? $query;
        }

        $record = $query->where($this->lookupColumn, $code)->first();

        return $record instanceof Model ? $record : null;
    }

    /** @param  array<string, mixed>  $context */
    private function runClosures(string $code, ?ScanInput $source, mixed $record, Closure $evaluate, array $context): ?ScanResult
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
