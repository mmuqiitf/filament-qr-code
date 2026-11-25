<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Concerns\HasAnnouncements;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasCodeNormalization;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasGs1Scan;
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
    use HasCodeNormalization;
    use HasFeedback;
    use HasGs1Scan;
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
     * Read every collected code as a GS1 element string. Each item in the
     * list is stored as the normalized bracketed form. An invalid label, or
     * one that misses a required AI, is rejected before it is added, with
     * the same translated reason as QrScanner::gs1().
     *
     * There is no `$fill`: a collector holds a list of labels, not a single
     * row of sibling fields. Use `require` for AIs every item must carry, or
     * QrScanner / a sequence step to write AIs into fields.
     *
     * @param  array<int|string, string>|Closure  $fill  must be empty
     * @param  list<int|string>|Closure  $require  AIs every item must carry, e.g. `['01', '17']`
     * @param  bool|Closure  $keepMissing  unused: the collector has no fill map
     */
    public function gs1(array|Closure $fill = [], array|Closure $require = [], bool|Closure $keepMissing = false): static
    {
        if (is_array($fill) && $fill !== []) {
            throw new InvalidArgumentException('QrCollector::gs1() does not take fill: each collected item is a GS1 string in the list. Pass require: for AIs every item must carry, or use QrScanner / QrScanSequence to write AIs into sibling fields.');
        }

        $this->configureGs1($fill, $require, $keepMissing);
        $this->registerCollectorItemRule();

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
     * @return array{passes: bool, message: ?string, code?: string}
     */
    #[ExposedLivewireMethod]
    public function checkCollectorItem(string $code): array
    {
        $code = $this->applyCodeNormalizer($code);

        $this->assertCollectorGs1HasNoFill();

        $normalized = $code;

        if ($this->isGs1()) {
            $elements = $this->getGs1ScanCheck()->inspect($code);

            if (is_string($elements)) {
                return ['passes' => false, 'message' => $elements];
            }

            $normalized = $elements->bracketed();
        }

        $rules = $this->resolveItemRules();

        if ($rules !== []) {
            $validator = Validator::make(
                ['code' => $normalized],
                ['code' => $rules],
            );

            if (! $validator->passes()) {
                $message = (string) $validator->errors()->first('code');

                return [
                    'passes' => false,
                    'message' => $message !== '' ? $message : (string) __('filament-qr-code::ui.scan_failed'),
                ];
            }
        } elseif (! $this->isGs1()) {
            return ['passes' => true, 'message' => null];
        }

        if ($this->isGs1()) {
            return ['passes' => true, 'message' => null, 'code' => $normalized];
        }

        return ['passes' => true, 'message' => null];
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
            'hasItemRules' => $this->collectorNeedsItemCheck(),
            ...ScanMessages::forAnnouncements(),
            ...$this->getAnnouncementsScanPayload(),
            ...$this->getAnnouncementComponentKey(),
            ...($this->collectorNeedsItemCheck() ? [
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

    protected function collectorNeedsItemCheck(): bool
    {
        return $this->hasItemRules() || $this->isGs1();
    }

    protected function assertCollectorGs1HasNoFill(): void
    {
        if (! $this->isGs1()) {
            return;
        }

        if ($this->getGs1ScanCheck()->fill !== []) {
            throw new InvalidArgumentException('QrCollector::gs1() does not take fill: each collected item is a GS1 string in the list. Pass require: for AIs every item must carry, or use QrScanner / QrScanSequence to write AIs into sibling fields.');
        }
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
