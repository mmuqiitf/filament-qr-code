<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Validation\ScanChecksRule;
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

    protected int|Closure|null $cooldownMs = null;

    protected bool $hasRegisteredScanChecksRule = false;

    protected ?Closure $onScannedCallback = null;

    protected ?Closure $onRejectedCallback = null;

    protected bool|Closure|null $clearOnReject = null;

    /** @param  array<int, mixed>|ValidationRule|Closure  $rules */
    public function validateUsing(array|ValidationRule|Closure $rules): static
    {
        $this->registerScanChecksRule();

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
            $this->rejectScan($result);

            return $result->toClient();
        }

        $previousState = $this->getState();

        try {
            $this->acceptScan($result);
        } catch (Throwable $exception) {
            report($exception);

            $this->state($previousState);

            $failure = ScanResult::reject($result->code, $result->source, __('filament-qr-code::ui.scan_failed'));

            event(new ScanRejected($failure, $this->getName()));

            return $failure->toClient();
        }

        return $result->toClient();
    }

    protected function acceptScan(ScanResult $result): void
    {
        $this->state($result->code);
        $this->callAfterStateUpdated();
        $this->evaluateScanCallback($this->onScannedCallback, $result);

        event(new ScanAccepted($result, $this->getName()));
        event(new QrCodeScanned(
            code: $result->code,
            source: ($result->source ?? ScanInput::Camera)->value,
            field: $this->getName(),
            context: ['record_key' => $result->record?->getKey()],
        ));

        // Continuous mode: the field is ready for the next scan straight away.
        if ($this->usesContinuousScanMode() && $this->shouldEmptyStateAfterAccept()) {
            $this->state(null);
        }
    }

    /** Provided by HasScanMode. */
    abstract public function isContinuous(): bool;

    /** Provided by HasScanMode. */
    abstract protected function shouldEmptyStateAfterAccept(): bool;

    /** Provided by HasScanMode. */
    abstract protected function getContinuousCooldownMs(): int;

    protected function usesContinuousScanMode(): bool
    {
        return $this->isContinuous();
    }

    public function onScanned(?Closure $callback): static
    {
        $this->onScannedCallback = $callback;

        return $this;
    }

    protected function evaluateScanCallback(?Closure $callback, ScanResult $result): void
    {
        if (! $callback instanceof Closure) {
            return;
        }

        $this->evaluate($callback, [
            'result' => $result,
            'code' => $result->code,
            'record' => $result->record,
            'source' => $result->source,
        ], [
            ScanResult::class => $result,
            ScanInput::class => $result->source,
        ]);
    }

    public function onRejected(?Closure $callback): static
    {
        $this->onRejectedCallback = $callback;

        return $this;
    }

    public function clearOnReject(bool|Closure $condition = true): static
    {
        $this->clearOnReject = $condition;

        return $this;
    }

    public function shouldClearOnReject(): bool
    {
        if ($this->clearOnReject === null) {
            return ConfigValue::bool('qr-code.scan.clear_on_reject', true);
        }

        return (bool) $this->evaluate($this->clearOnReject);
    }

    protected function rejectScan(ScanResult $result): void
    {
        if ($this->shouldClearOnReject()) {
            $this->state(null);
        }

        $this->evaluateScanCallback($this->onRejectedCallback, $result);

        event(new ScanRejected($result, $this->getName()));
    }

    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        try {
            return $this->runScanCheckStages($code, $source);
        } catch (ValidationException $exception) {
            return ScanResult::reject($code, $source, $exception->validator->errors()->first());
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
        $this->registerScanChecksRule();

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
            attributes: ['code' => $this->getScanAttributeLabel()],
        );

        if ($validator->passes()) {
            return null;
        }

        return ScanResult::reject($code, $source, (string) $validator->errors()->first('code'));
    }

    protected function getScanAttributeLabel(): string
    {
        $label = $this->getLabel();

        if ($label instanceof Htmlable) {
            return strip_tags($label->toHtml());
        }

        return (string) $label;
    }

    public function hasServerScanChecks(): bool
    {
        return $this->scanRules !== []
            || $this->scanCheckClosures !== []
            || $this->lookupModel !== null
            || $this->onScannedCallback !== null
            || $this->onRejectedCallback !== null
            || $this->usesContinuousScanMode();
    }

    protected function registerScanChecksRule(): void
    {
        if ($this->hasRegisteredScanChecksRule) {
            return;
        }

        $this->hasRegisteredScanChecksRule = true;

        $this->rule(fn (): ScanChecksRule => new ScanChecksRule($this));
    }

    public function cooldown(int|Closure $milliseconds): static
    {
        $this->cooldownMs = $milliseconds;

        return $this;
    }

    public function getCooldownMs(): int
    {
        if ($this->cooldownMs !== null) {
            return max(0, (int) $this->evaluate($this->cooldownMs));
        }

        if ($this->usesContinuousScanMode()) {
            return $this->getContinuousCooldownMs();
        }

        if (! $this->hasServerScanChecks()) {
            return 0;
        }

        return ConfigValue::int('qr-code.scan.cooldown_ms', 1500, minimum: 0);
    }

    /** @return array{serverCheck: bool, cooldownMs: int, clearOnReject: bool, scanFailedMessage: string, componentKey?: string} */
    public function getScanChecksScanPayload(): array
    {
        $payload = [
            'serverCheck' => $this->hasServerScanChecks(),
            'cooldownMs' => $this->getCooldownMs(),
            'clearOnReject' => $this->shouldClearOnReject(),
            'scanFailedMessage' => __('filament-qr-code::ui.scan_failed'),
        ];

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
