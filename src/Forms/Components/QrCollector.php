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
use Mmuqiitf\FilamentQrCode\Validation\CollectorMaxItems;
use Mmuqiitf\FilamentQrCode\Validation\CollectorQuantity;
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

    protected bool|Closure $quantity = false;

    protected int|Closure|null $maxItems = null;

    protected bool|Closure $csvImport = false;

    protected bool|Closure $csvExport = false;

    protected string|Closure|null $csvFileName = null;

    /** @var array<int, mixed>|Closure */
    protected array|Closure $itemRules = [];

    protected bool $hasRegisteredCollectorItemRule = false;

    protected bool $hasRegisteredMaxItemsRule = false;

    protected bool $hasRegisteredQuantityRule = false;

    /** Accept the same code more than once in the list (browser-side check). */
    public function allowDuplicates(bool|Closure $condition = true): static
    {
        $this->allowDuplicates = $condition;

        return $this;
    }

    /** Track a quantity per unique code so rescans increment the count instead of rejecting duplicates. */
    public function quantity(bool|Closure $condition = true): static
    {
        $this->quantity = $condition;
        $this->registerQuantityRule();

        return $this;
    }

    /** Cap how many items the collector can hold, enforced in the browser and on submit. */
    public function maxItems(int|Closure|null $max): static
    {
        if (is_int($max) && $max < 1) {
            throw new InvalidArgumentException('QrCollector::maxItems() must be at least 1, or null for unlimited.');
        }

        $this->maxItems = $max;
        $this->registerMaxItemsRule();

        return $this;
    }

    /** Enable importing collected items from a CSV file (respects normalizeWith(), gs1(), itemRules(), and maxItems()). */
    public function csvImport(bool|Closure $condition = true): static
    {
        $this->csvImport = $condition;

        return $this;
    }

    /** Enable exporting collected items as a CSV download. */
    public function csvExport(bool|Closure $condition = true, string|Closure|null $fileName = null): static
    {
        $this->csvExport = $condition;

        if ($fileName !== null) {
            $this->csvFileName = $fileName;
        }

        return $this;
    }

    /** Enable both CSV import and CSV export on this collector. */
    public function csv(bool|Closure $import = true, bool|Closure $export = true, string|Closure|null $fileName = null): static
    {
        $this->csvImport($import);
        $this->csvExport($export, $fileName);

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
     * QrScanner / a sequence step to write AIs into sibling fields.
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

    /**
     * Format collected items as an RFC 4180 CSV string (includes a `qty` column in quantity mode).
     *
     * @param  iterable<int|string, mixed>  $items
     */
    public function toCsv(iterable $items): string
    {
        $hasQuantity = $this->hasQuantity();
        $lines = [$hasQuantity ? 'code,qty' : 'code'];

        foreach ($items as $item) {
            if (is_array($item) && array_key_exists('code', $item) && is_scalar($item['code'])) {
                $code = (string) $item['code'];
                $qty = isset($item['qty']) && is_numeric($item['qty']) ? max(1, (int) $item['qty']) : 1;
            } elseif (is_scalar($item)) {
                $code = (string) $item;
                $qty = 1;
            } else {
                continue;
            }

            if ($hasQuantity) {
                $lines[] = $this->escapeCsvCell($code).','.$qty;
            } else {
                $lines[] = $this->escapeCsvCell($code);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Parse and validate a CSV upload against the collector's normalizer, GS1, itemRules, duplicates, and maxItems settings.
     *
     * @param  array<int, mixed>|null  $existing
     * @return array{items: list<string|array{code: string, qty: int}>, added: int, rejected: list<array{row: int, code: string, reason: string}>}
     */
    #[ExposedLivewireMethod]
    public function importCollectorCsv(string $csv, ?array $existing = null): array
    {
        $hasQuantity = $this->hasQuantity();
        $maxItems = $this->getMaxItems();
        $allowDuplicates = $this->isDuplicatesAllowed();

        $seed = $existing ?? ($this->currentStateArray());
        $items = $this->normalizeExistingItems($seed, $hasQuantity);

        if (! $this->isCsvImportEnabled()) {
            return [
                'items' => $items,
                'added' => 0,
                'rejected' => [],
            ];
        }

        $rows = $this->parseCsvRows($csv);

        if ($rows === []) {
            return [
                'items' => $items,
                'added' => 0,
                'rejected' => [],
            ];
        }

        $codeCol = 0;
        $qtyCol = $hasQuantity ? 1 : null;
        $startIndex = 0;

        $header = array_map(fn (string $cell): string => strtolower(trim($cell)), $rows[0]);
        $foundCodeCol = array_search('code', $header, true);

        if ($foundCodeCol !== false) {
            $codeCol = (int) $foundCodeCol;
            $foundQtyCol = array_search('qty', $header, true);

            if ($foundQtyCol === false) {
                $foundQtyCol = array_search('quantity', $header, true);
            }

            $qtyCol = $foundQtyCol !== false ? (int) $foundQtyCol : ($hasQuantity ? 1 : null);
            $startIndex = 1;
        }

        $added = 0;
        $rejected = [];
        $count = count($rows);

        for ($i = $startIndex; $i < $count; $i++) {
            [$rowNumber, $cols] = [$i + 1, $rows[$i]];
            $rawCode = (string) ($cols[$codeCol] ?? '');

            if (trim($rawCode) === '') {
                $rejected[] = [
                    'row' => $rowNumber,
                    'code' => '',
                    'reason' => (string) __('filament-qr-code::ui.scan_empty'),
                ];

                continue;
            }

            $check = $this->checkCollectorItem($rawCode);

            if (! $check['passes']) {
                $rejected[] = [
                    'row' => $rowNumber,
                    'code' => $this->applyCodeNormalizer($rawCode),
                    'reason' => $check['message'] ?? (string) __('filament-qr-code::ui.scan_failed'),
                ];

                continue;
            }

            $code = $check['code'] ?? $this->applyCodeNormalizer($rawCode);

            if ($code === '') {
                $rejected[] = [
                    'row' => $rowNumber,
                    'code' => '',
                    'reason' => (string) __('filament-qr-code::ui.scan_empty'),
                ];

                continue;
            }

            if ($hasQuantity) {
                $rowQty = 1;

                if ($qtyCol !== null && array_key_exists($qtyCol, $cols) && trim($cols[$qtyCol]) !== '') {
                    $rawQty = trim($cols[$qtyCol]);

                    if (preg_match('/^\d+$/', $rawQty) !== 1 || (int) $rawQty < 1) {
                        $rejected[] = [
                            'row' => $rowNumber,
                            'code' => $code,
                            'reason' => (string) __('filament-qr-code::ui.csv_invalid_quantity'),
                        ];

                        continue;
                    }

                    $rowQty = (int) $rawQty;
                }

                $existingIndex = $this->findQuantityIndex($items, $code);

                if ($existingIndex !== null) {
                    /** @var array{code: string, qty: int} $entry */
                    $entry = $items[$existingIndex];
                    $entry['qty'] += $rowQty;
                    $items[$existingIndex] = $entry;
                    $added++;

                    continue;
                }

                if ($maxItems !== null && count($items) >= $maxItems) {
                    $rejected[] = [
                        'row' => $rowNumber,
                        'code' => $code,
                        'reason' => (string) __('filament-qr-code::ui.collector_max_items', ['max' => $maxItems]),
                    ];

                    continue;
                }

                $items[] = ['code' => $code, 'qty' => $rowQty];
                $added++;

                continue;
            }

            if (! $allowDuplicates && in_array($code, $items, true)) {
                $rejected[] = [
                    'row' => $rowNumber,
                    'code' => $code,
                    'reason' => (string) __('filament-qr-code::ui.duplicate_codes'),
                ];

                continue;
            }

            if ($maxItems !== null && count($items) >= $maxItems) {
                $rejected[] = [
                    'row' => $rowNumber,
                    'code' => $code,
                    'reason' => (string) __('filament-qr-code::ui.collector_max_items', ['max' => $maxItems]),
                ];

                continue;
            }

            $items[] = $code;
            $added++;
        }

        return [
            'items' => array_values($items),
            'added' => $added,
            'rejected' => $rejected,
        ];
    }

    /** Whether any Laravel validation rules are configured on items. */
    public function hasItemRules(): bool
    {
        return $this->resolveItemRules() !== [];
    }

    /** Whether duplicate codes are allowed in non-quantity mode. */
    public function isDuplicatesAllowed(): bool
    {
        return (bool) $this->evaluate($this->allowDuplicates);
    }

    /** Whether quantity tracking per code is enabled. */
    public function hasQuantity(): bool
    {
        return (bool) $this->evaluate($this->quantity);
    }

    /** Maximum number of items allowed in the collector, or null when unlimited. */
    public function getMaxItems(): ?int
    {
        $max = $this->evaluate($this->maxItems);

        if ($max === null) {
            return null;
        }

        if (! is_int($max) && ! is_numeric($max)) {
            throw new InvalidArgumentException('QrCollector::maxItems() must evaluate to an integer or null.');
        }

        $value = (int) $max;

        if ($value < 1) {
            throw new InvalidArgumentException('QrCollector::maxItems() must be at least 1, or null for unlimited.');
        }

        return $value;
    }

    /** Whether CSV import is enabled on this collector. */
    public function isCsvImportEnabled(): bool
    {
        return (bool) $this->evaluate($this->csvImport);
    }

    /** Whether CSV export is enabled on this collector. */
    public function isCsvExportEnabled(): bool
    {
        return (bool) $this->evaluate($this->csvExport);
    }

    /** Filename used when exporting collected items to CSV. */
    public function getCsvFileName(): string
    {
        $custom = $this->evaluate($this->csvFileName);

        if (is_string($custom) && trim($custom) !== '') {
            return trim($custom);
        }

        return 'collected-codes.csv';
    }

    /** Minimum delay in milliseconds between camera scans. */
    public function getDelayBetweenScansMs(): int
    {
        return (int) $this->evaluate($this->delayBetweenScansMs);
    }

    /** @return array<string, mixed> */
    public function getComponentScanPayload(): array
    {
        $hasQuantity = $this->hasQuantity();
        $maxItems = $this->getMaxItems();
        $csvImport = $this->isCsvImportEnabled();
        $csvExport = $this->isCsvExportEnabled();
        $collectorMessages = ($maxItems !== null || $csvImport) ? ScanMessages::forCollector() : [];

        return [
            'allowDuplicates' => $this->isDuplicatesAllowed(),
            'delayBetweenScansMs' => $this->getDelayBetweenScansMs(),
            // $wire proxies every name, so the browser cannot probe for the hook itself.
            'wireScanHandler' => $this->hostDefinesCollectorScanHandler(),
            'hasItemRules' => $this->collectorNeedsItemCheck(),
            ...ScanMessages::forAnnouncements(),
            ...$this->getAnnouncementsScanPayload(),
            ...$this->getAnnouncementComponentKey(),
            ...($hasQuantity ? ['quantity' => true] : []),
            ...($maxItems !== null ? [
                'maxItems' => $maxItems,
                'maxItemsMessage' => $collectorMessages['maxItemsMessage'],
            ] : []),
            ...($csvImport ? [
                'csvImport' => true,
                'csvImportSummary' => $collectorMessages['csvImportSummary'],
                'csvImportSuccess' => $collectorMessages['csvImportSuccess'],
                'csvRejectedRow' => $collectorMessages['csvRejectedRow'],
                'scanFailedMessage' => ScanMessages::scanFailed(),
                ...$this->componentKeyPayload(),
            ] : []),
            ...($csvExport ? [
                'csvExport' => true,
                'csvFileName' => $this->getCsvFileName(),
            ] : []),
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

    protected function registerMaxItemsRule(): void
    {
        if ($this->hasRegisteredMaxItemsRule) {
            return;
        }

        $this->hasRegisteredMaxItemsRule = true;

        $this->rule(fn (): CollectorMaxItems => new CollectorMaxItems($this));
    }

    protected function registerQuantityRule(): void
    {
        if ($this->hasRegisteredQuantityRule) {
            return;
        }

        $this->hasRegisteredQuantityRule = true;

        $this->rule(fn (): CollectorQuantity => new CollectorQuantity($this));
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

    /** @return array<int, mixed> */
    protected function currentStateArray(): array
    {
        if (! isset($this->container)) {
            return [];
        }

        try {
            $state = $this->getState();
        } catch (\Throwable) {
            return [];
        }

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<int|string, mixed>  $seed
     * @return list<string|array{code: string, qty: int}>
     */
    protected function normalizeExistingItems(array $seed, bool $hasQuantity): array
    {
        $items = [];

        foreach ($seed as $entry) {
            if (is_array($entry) && array_key_exists('code', $entry) && is_scalar($entry['code'])) {
                $code = (string) $entry['code'];
                $qty = isset($entry['qty']) && is_numeric($entry['qty']) ? max(1, (int) $entry['qty']) : 1;
            } elseif (is_scalar($entry)) {
                $code = (string) $entry;
                $qty = 1;
            } else {
                continue;
            }

            if ($code === '') {
                continue;
            }

            if (! $hasQuantity) {
                $items[] = $code;

                continue;
            }

            $existingIndex = $this->findQuantityIndex($items, $code);

            if ($existingIndex !== null) {
                /** @var array{code: string, qty: int} $current */
                $current = $items[$existingIndex];
                $current['qty'] += $qty;
                $items[$existingIndex] = $current;
            } else {
                $items[] = ['code' => $code, 'qty' => $qty];
            }
        }

        return array_values($items);
    }

    /**
     * @param  array<int, string|array{code: string, qty: int}>  $items
     */
    protected function findQuantityIndex(array $items, string $code): ?int
    {
        foreach ($items as $index => $item) {
            if (is_array($item) && $item['code'] === $code) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return list<list<string>>
     */
    protected function parseCsvRows(string $csv): array
    {
        $csv = (string) preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $csv = str_replace(["\r\n", "\r"], "\n", $csv);
        $lines = explode("\n", $csv);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $parsed = str_getcsv($line, ',', '"', '');
            $rows[] = array_map(fn (?string $cell): string => $cell ?? '', $parsed);
        }

        return $rows;
    }

    protected function escapeCsvCell(string $value): string
    {
        if (preg_match('/[",\r\n]/', $value) === 1) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
