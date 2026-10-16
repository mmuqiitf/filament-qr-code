<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Throwable;

trait InteractsWithScans
{
    /** @var array<int, mixed> */
    protected array $scanRules = [];

    /** @var array<int, Closure> */
    protected array $scanCheckClosures = [];

    /** @var class-string<Model>|null */
    protected ?string $lookupModel = null;

    protected string $lookupColumn = 'code';

    protected ?Closure $modifyLookupQueryUsing = null;

    /** @param  array<int, mixed>|ValidationRule|Closure  $rules */
    public function validateUsing(array|ValidationRule|Closure $rules): static
    {
        if ($rules instanceof Closure) {
            $this->scanCheckClosures[] = $rules;

            return $this;
        }

        $this->scanRules = [...$this->scanRules, ...(is_array($rules) ? $rules : [$rules])];

        return $this;
    }

    /** @return array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed} */
    #[ExposedLivewireMethod]
    public function handleScan(string $code, string $source): array
    {
        $input = ScanInput::fromClient($source);
        $code = trim($code);

        if ($this->isDisabled() || $this->isReadOnly()) {
            return ScanResult::reject($code, $input, __('filament-qr-code::ui.scan_rejected'))->toClient();
        }

        $result = $this->runScanChecks($code, $input);

        if (! $result->passes) {
            return $result->toClient();
        }

        $this->state($result->code);
        $this->callAfterStateUpdated();

        return $result->toClient();
    }

    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        try {
            return $this->runScanCheckStages($code, $source);
        } catch (ValidationException $exception) {
            return ScanResult::reject($code, $source, (string) collect($exception->errors())->flatten()->first());
        } catch (Throwable $exception) {
            report($exception);

            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_failed'));
        }
    }

    protected function runScanCheckStages(string $code, ?ScanInput $source): ScanResult
    {
        if ($code === '') {
            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_empty'));
        }

        if ($rejection = $this->checkScanRules($code, $source)) {
            return $rejection;
        }

        $record = $this->lookUpScanRecord($code, $source);

        if ($record instanceof ScanResult) {
            return $record;
        }

        return $this->runScanCheckClosures($code, $source, $record)
            ?? ScanResult::accept($code, $source, $record);
    }

    protected function runScanCheckClosures(string $code, ?ScanInput $source, ?Model $record): ?ScanResult
    {
        foreach ($this->scanCheckClosures as $check) {
            $outcome = $this->evaluate($check, [
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

    /** @param  class-string<Model>  $model */
    public function lookup(string $model, string $column = 'code', ?Closure $modifyQueryUsing = null): static
    {
        $this->lookupModel = $model;
        $this->lookupColumn = $column;
        $this->modifyLookupQueryUsing = $modifyQueryUsing;

        return $this;
    }

    protected function lookUpScanRecord(string $code, ?ScanInput $source): Model|ScanResult|null
    {
        if ($this->lookupModel === null) {
            return null;
        }

        $query = $this->lookupModel::query();

        if ($this->modifyLookupQueryUsing instanceof Closure) {
            $query = $this->evaluate($this->modifyLookupQueryUsing, ['query' => $query], [Builder::class => $query]) ?? $query;
        }

        $record = $query->where($this->lookupColumn, $code)->first();

        if ($record instanceof Model) {
            return $record;
        }

        return ScanResult::reject($code, $source, __('filament-qr-code::ui.record_not_found', ['code' => $code]));
    }

    protected function checkScanRules(string $code, ?ScanInput $source): ?ScanResult
    {
        if ($this->scanRules === []) {
            return null;
        }

        $validator = Validator::make(
            ['code' => $code],
            ['code' => $this->scanRules],
            attributes: ['code' => (string) $this->getLabel()],
        );

        if ($validator->passes()) {
            return null;
        }

        return ScanResult::reject($code, $source, (string) $validator->errors()->first('code'));
    }

    public function hasServerScanChecks(): bool
    {
        return $this->scanRules !== []
            || $this->scanCheckClosures !== []
            || $this->lookupModel !== null;
    }

    /** @return array{serverCheck: bool, componentKey?: string} */
    public function getScanChecksScanPayload(): array
    {
        $payload = ['serverCheck' => $this->hasServerScanChecks()];

        try {
            $key = $this->getKey();
        } catch (Throwable) {
            return $payload;
        }

        if (blank($key)) {
            return $payload;
        }

        return [...$payload, 'componentKey' => $key];
    }
}
