# Batch Collecting

> Back to [README](../README.md) · [Scanning](scanning.md) · [Generating](generating.md) · [Reference](reference.md)

Continuous camera multi-scan for inventory and repeater flows. For cashier/POS gun flows, see the [POS tutorial](scanning.md#tutorial-cashier-pos-with-a-handheld-scanner).

## Batch Collector Scanning (`QrCollector`, `QrCollectAction`)

![Shelf audit page with three product labels collected and an unknown code flagged by the server-side lookup](images/shelf-audit-collector.png)

*A warehouse shelf audit: each wedge or camera scan is looked up against seeded products, repeats are ignored, and unknown codes are flagged so they can be removed (×) before submitting.*

![Shelf audit after recording a count, with the list cleared and the count listed under Recent counts](images/shelf-audit-recorded.png)

*After "Record count" the collector clears and the count shows which products were not seen.*

See the full example app: [`app/Filament/Pages/ShelfAudit.php`](https://github.com/mmuqiitf/filament-qr-code-example/blob/main/app/Filament/Pages/ShelfAudit.php).

### In Form Repeaters

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('scanned_items')
    ->allowDuplicates(false)
    ->delayBetweenScans(1200)
    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
    ->preferRearCamera();
```

`QrCollector` and `QrCollectAction` also share `->preset(ScannerPreset::Warehouse)` and `->scanRegion(0.85, 0.35)` with `QrScanner` and `QrScanSequence`.

### As a Table / Repeater Action

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

$table->headerActions([
    QrCollectAction::make()
        ->allowDuplicates(false)
        ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
        ->onScan(fn ($code) => logger()->info("Collected {$code}")),
]);
```

Each scan notifies the server through exactly one channel: `$wire.handleCollectorScan($code)` when the host Livewire component defines it, otherwise a `qr-collector-item-added` window event (with `{ code }`) that the host forwards — e.g. `x-on:qr-collector-item-added.window` calling your Livewire method, which can then invoke `$action->handleScan($code)` to run the `onScan` callback. The component picks deterministically, so a scan is never handled twice.

Every `handleScan()` call also fires a `QrCodeScanned` event — see [Audit scans](customizing.md#audit-scans).

Custom terminators and minimum lengths are configurable via `->hardwareScanner(terminators: [...], minBarcodeLength: 3)` and feedback pitch/duration via `->beepFrequency(660)`, `->beepDuration(120)`, `->vibrateDuration(200)`. Server-side, `->distinctItems()` rejects duplicate codes on submit to match `->allowDuplicates(false)` in the browser.

`->itemRules()` runs Laravel validation rules on each code before the browser adds it, and again when the field is submitted. The first failing code fails the field. An empty list accepts every code. The collect action modal does not run these rules: it is not a schema field, so keep that check on `QrCollector`.

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('scanned_items')
    ->itemRules(['regex:/^PRD-\d+$/']);
```

`->normalizeWith()` is the same as on a scanner or sequence: trim (on by default), optional uppercase, and optional prefix stripping run in the browser before duplicate detection and again on the server before GS1 checks and `itemRules()`.

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('scanned_items')
    ->normalizeWith(uppercase: true, stripPrefix: [']C1', ']Q1'])
    ->itemRules(['regex:/^SKU-/']);
```

`->gs1(require: ['01', '17'])` treats every item as a GS1 label: the list stores the normalized bracketed string, and an invalid or incomplete label is rejected before it is added. There is no `fill` — use `QrScanner` or a [sequence step](gs1.md#scanning-gs1-labels) to write AIs into sibling fields. `itemRules` run after the GS1 check and see the bracketed string. The collect action modal does not run `gs1()`. See [Scanning GS1 labels](gs1.md#scanning-gs1-labels).

### Quantity tracking (`->quantity()`)

Quantity tracking is opt-in and off by default (`QrCollector` stores a plain `list<string>` unless `->quantity()` is set). When enabled, rescanning an existing code increments its quantity instead of rejecting a duplicate, and operators can adjust quantities inline with `-`, `+`, or direct number entry.

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('items')
    ->quantity()
    ->normalizeWith(uppercase: true);
```

When `->quantity()` is enabled, the field state is `list<array{code: string, qty: int}>` (for example `[['code' => 'PRD-1', 'qty' => 3]]`), and submit-time validation verifies each row has a non-empty `code` and an integer `qty >= 1`.

### Maximum items (`->maxItems()`)

Cap how many collected entries the component accepts in both the browser and on form submit with `->maxItems(?int $max)`. Passing `0` or a negative integer throws `InvalidArgumentException`; pass `null` for unlimited.

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('items')
    ->quantity()
    ->maxItems(25);
```

In standard mode, `maxItems` caps the total number of scanned codes. In quantity mode, `maxItems` caps the number of distinct line items (`count($items)`), so operators can continue rescanning an already-listed code to increment its quantity even when the item limit is reached.

### CSV import and export (`->csvImport()`, `->csvExport()`, `->csv()`)

Enable CSV download and upload buttons in the collector toolbar:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('items')
    ->quantity()
    ->maxItems(100)
    ->csv(fileName: 'shelf-audit.csv')
    ->itemRules(['regex:/^PRD-\d+$/']);
```

Or enable each direction individually with `->csvImport()` and `->csvExport(fileName: '...')`, and format items from PHP with `$collector->toCsv($items)`.

- **Export** writes RFC 4180 CSV (`code` header in standard mode, `code,qty` header in quantity mode).
- **Import** accepts UTF-8 BOM or plain UTF-8 CSV with an optional `code` / `qty` (or `quantity`) header, runs `normalizeWith()`, `gs1()`, `itemRules()`, duplicate rules, and `maxItems()` on every row on the server, merges quantities in quantity mode, and renders a dismissible summary listing any rejected rows with their row number and reason.
