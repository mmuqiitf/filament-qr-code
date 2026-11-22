<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\Validator;
use Mmuqiitf\FilamentQrCode\Concerns\HasAnnouncements;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasRenderHookScopes;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Support\ScanMessages;
use Mmuqiitf\FilamentQrCode\Validation\CollectorItemRules;
use Mmuqiitf\FilamentQrCode\Validation\DistinctCodes;

class QrCollector extends Field
{
    use HasAnnouncements;
    use HasCameraScanning;
    use HasFeedback;
    use HasHardwareScanner;
    use HasRenderHookScopes;
    use HasScanPayload;

    protected string $view = 'filament-qr-code::components.qr-collector';

    protected bool|Closure $allowDuplicates = false;

    protected int|Closure $delayBetweenScansMs = 1200;

    /** @var array<int, mixed>|Closure */
    protected array|Closure $itemRules = [];

    protected bool $hasRegisteredCollectorItemRule = false;

    /** Accept the same code more than once in the list (browser-side check). */
    public function allowDuplicates(bool|Closure $condition = true): static
    {
        $this->allowDuplicates = $condition;

        return $this;
    }

    /** Pause in milliseconds before the next camera scan is accepted. */
    public function delayBetweenScans(int|Closure $ms): static
    {
        $this->delayBetweenScansMs = $ms;

        return $this;
    }

    /**
     * Server-side duplicate guard to match the client-side
     * allowDuplicates(false): identical codes fail submit-time validation.
     */
    public function distinctItems(bool|Closure $condition = true): static
    {
        $this->rule('array', $condition);
        $this->rule(new DistinctCodes, $condition);

        return $this;
    }

    /**
     * Laravel rules for one collected code. The browser asks before adding
     * the code, and submit fails on the first code that does not pass.
     * Calling this again replaces the rules. An empty list accepts every code.
     *
     * @param  array<int, mixed>|Closure  $rules
     */
    public function itemRules(array|Closure $rules): static
    {
        $this->itemRules = $rules;
        $this->registerCollectorItemRule();

        return $this;
    }

    /**
     * Live check used by the collector. Empty rules pass.
     *
     * @return array{passes: bool, message: ?string}
     */
    #[ExposedLivewireMethod]
    public function checkCollectorItem(string $code): array
    {
        $rules = $this->resolveItemRules();

        if ($rules === []) {
            return ['passes' => true, 'message' => null];
        }

        $validator = Validator::make(
            ['code' => $code],
            ['code' => $rules],
        );

        if ($validator->passes()) {
            return ['passes' => true, 'message' => null];
        }

        $message = (string) $validator->errors()->first('code');

        return [
            'passes' => false,
            'message' => $message !== '' ? $message : (string) __('filament-qr-code::ui.scan_failed'),
        ];
    }

    public function hasItemRules(): bool
    {
        return $this->resolveItemRules() !== [];
    }

    public function isDuplicatesAllowed(): bool
    {
        return (bool) $this->evaluate($this->allowDuplicates);
    }

    public function getDelayBetweenScansMs(): int
    {
        return (int) $this->evaluate($this->delayBetweenScansMs);
    }

    /** @return array<string, mixed> */
    public function getComponentScanPayload(): array
    {
        return [
            'allowDuplicates' => $this->isDuplicatesAllowed(),
            'delayBetweenScansMs' => $this->getDelayBetweenScansMs(),
            // $wire proxies every name, so the browser cannot probe for the hook itself.
            'wireScanHandler' => $this->hostDefinesCollectorScanHandler(),
            'hasItemRules' => $this->hasItemRules(),
            ...ScanMessages::forAnnouncements(),
            ...$this->getAnnouncementsScanPayload(),
            ...$this->getAnnouncementComponentKey(),
            ...($this->hasItemRules() ? [
                'scanFailedMessage' => ScanMessages::scanFailed(),
                ...$this->componentKeyPayload(),
            ] : []),
        ];
    }

    /**
     * Custom announcement text is resolved on the server, which needs the key.
     *
     * @return array{componentKey?: string}
     */
    protected function getAnnouncementComponentKey(): array
    {
        if ($this->announceUsing === null) {
            return [];
        }

        return $this->componentKeyPayload();
    }

    /**
     * @return array{componentKey?: string}
     */
    protected function componentKeyPayload(): array
    {
        try {
            $key = $this->getKey();
        } catch (\Throwable) {
            return [];
        }

        return filled($key) ? ['componentKey' => $key] : [];
    }

    protected function registerCollectorItemRule(): void
    {
        if ($this->hasRegisteredCollectorItemRule) {
            return;
        }

        $this->hasRegisteredCollectorItemRule = true;

        $this->rule(fn (): CollectorItemRules => new CollectorItemRules($this));
    }

    /**
     * @return array<int, mixed>
     */
    protected function resolveItemRules(): array
    {
        $rules = $this->evaluate($this->itemRules);

        return is_array($rules) ? $rules : [];
    }

    /**
     * Fields reach Livewire through their schema container; a field built
     * outside a schema (e.g. in a unit test) has no host to call.
     */
    protected function hostDefinesCollectorScanHandler(): bool
    {
        if (! isset($this->container)) {
            return false;
        }

        try {
            $livewire = $this->getContainer()->getLivewire();
        } catch (\TypeError) {
            // A schema made without a Livewire component returns null through a non-nullable getter.
            return false;
        }

        return method_exists($livewire, 'handleCollectorScan');
    }
}
