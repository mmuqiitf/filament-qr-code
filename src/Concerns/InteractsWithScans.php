<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Support\ConfigValue;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckPipeline;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Validation\ScanChecksRule;
use Throwable;

trait InteractsWithScans
{
    /** @var array<int, mixed> */
    protected array $scanRules = [];

    /** @var array<int, Closure> */
    protected array $scanCheckClosures = [];

    /** @var class-string<Model>|Closure|null */
    protected string|Closure|null $lookupModel = null;

    protected string|Closure $lookupColumn = 'code';

    protected ?Closure $lookupResolver = null;

    protected bool $lookupFallback = false;

    /** @var array<int, ScanCheckStage|class-string<ScanCheckStage>> */
    protected array $scanStages = [];

    protected ?Closure $scanNormalizer = null;

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
        $code = $this->normalizeScannedCode($code);

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

            event(new ScanRejected($failure, $this->getName(), ...$this->scanEventContext()));

            return $failure->toClient();
        }

        return $result->toClient();
    }

    protected function acceptScan(ScanResult $result): void
    {
        $this->state($result->code);
        $this->callAfterStateUpdated();
        $this->evaluateScanCallback($this->onScannedCallback, $result);

        event(new ScanAccepted($result, $this->getName(), ...$this->scanEventContext()));
        event(new QrCodeScanned(
            code: $result->code,
            source: ($result->source ?? ScanInput::Camera)->value,
            field: $this->getName(),
            context: ['record_key' => $result->recordKey(), 'state_path' => $this->scanEventContext()['statePath']],
        ));

        // Continuous mode: the field is ready for the next scan straight away.
        if ($this->usesContinuousScanMode() && $this->shouldEmptyStateAfterAccept()) {
            $this->state(null);
        }
    }

    /** Provided by HasCodeNormalization. */
    abstract protected function applyCodeNormalizer(string $code): string;

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

        event(new ScanRejected($result, $this->getName(), ...$this->scanEventContext()));
    }

    /**
     * Where a scan happened, for event listeners. Strings only, so queued
     * listeners can serialize the events.
     *
     * @return array{statePath: ?string, livewireClass: ?string, livewireId: ?string}
     */
    protected function scanEventContext(): array
    {
        try {
            $livewire = $this->getLivewire();
            $statePath = $this->getStatePath();
        } catch (Throwable) {
            return ['statePath' => null, 'livewireClass' => null, 'livewireId' => null];
        }

        return ['statePath' => $statePath, 'livewireClass' => $livewire::class, 'livewireId' => $livewire->getId()];
    }

    /**
     * The code every check sees: the normalizeWith() presets (or a trim), then
     * the normalizeUsing() closure.
     */
    public function normalizeScannedCode(string $code): string
    {
        $code = $this->applyCodeNormalizer($code);

        if (! $this->scanNormalizer instanceof Closure) {
            return $code;
        }

        $normalized = $this->evaluate($this->scanNormalizer, ['code' => $code, 'rawValue' => $code, 'state' => $code]);

        return is_scalar($normalized) ? trim((string) $normalized) : $code;
    }

    /**
     * Find the record yourself (an API, a composite key, a non-Eloquent store).
     * Replaces lookup() on this field unless $fallback, when it runs only after
     * the model lookup found nothing. Return null or false for "not found".
     */
    public function lookupUsing(?Closure $resolver, bool $fallback = false): static
    {
        $this->registerScanChecksRule();

        $this->lookupResolver = $resolver;
        $this->lookupFallback = $fallback;

        return $this;
    }

    /**
     * Add custom checks that run after the rules, lookup and closures.
     *
     * @param  ScanCheckStage|class-string<ScanCheckStage>  ...$stages
     */
    public function pipe(ScanCheckStage|string ...$stages): static
    {
        $this->registerScanChecksRule();

        foreach ($stages as $stage) {
            $this->scanStages[] = ScanCheckPipeline::ensureStage($stage);
        }

        return $this;
    }

    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        return $this->getScanCheckPipeline()->run(
            $code,
            $source,
            fn (Closure $closure, array $named = [], array $typed = []): mixed => $this->evaluate($closure, $named, $typed),
            $this->getScanAttributeLabel(),
        );
    }

    public function getScanCheckPipeline(): ScanCheckPipeline
    {
        /** @var class-string<Model>|null $model */
        $model = $this->evaluate($this->lookupModel);

        return new ScanCheckPipeline(
            rules: $this->scanRules,
            closures: $this->scanCheckClosures,
            lookupModel: $model,
            lookupColumn: (string) $this->evaluate($this->lookupColumn),
            modifyLookupQueryUsing: $this->modifyLookupQueryUsing,
            lookupResolver: $this->lookupResolver,
            lookupFallback: $this->lookupFallback,
            stages: $this->scanStages,
            component: $this,
        );
    }

    /** @param  class-string<Model>|Closure  $model */
    public function lookup(string|Closure $model, string|Closure $column = 'code', ?Closure $modifyQueryUsing = null): static
    {
        $this->registerScanChecksRule();

        $this->lookupModel = $model;
        $this->lookupColumn = $column;
        $this->modifyLookupQueryUsing = $modifyQueryUsing;

        return $this;
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
            || $this->lookupResolver !== null
            || $this->scanStages !== []
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
