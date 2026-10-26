# AI guide

> For coding agents (and the people reviewing their work) adding `mmuqiitf/filament-qr-code` to a Laravel + Filament v5 app. Back to [README](../README.md) · [API map](api.md) · [Getting started](getting-started.md)

This guide is written for agents that build *with* the package. Agents working *on* the package should read `AGENTS.md` in the repository instead. The whole doc set is also available as one file in [`llms-full.txt`](../llms-full.txt), with an index in [`llms.txt`](../llms.txt). The words below (QR Scanner Field, Station Listener, Scan Sequence Container, Batch Collector Scanning, Hardware Scanner, Scan Feedback, …) follow the project vocabulary in [CONTEXT.md](https://github.com/mmuqiitf/filament-qr-code/blob/main/CONTEXT.md).

## Component picker

| The user wants… | Use | Namespace |
| --- | --- | --- |
| one input filled by camera, image upload, typing or a gun | QR Scanner Field: `QrScanner` | `Mmuqiitf\FilamentQrCode\Forms\Components` |
| a gun driving several fields, no camera UI (POS, station) | Station Listener: `QrHardwareScannerListener` | `Mmuqiitf\FilamentQrCode\Forms\Components` |
| one camera filling many fields in a fixed order | Scan Sequence Container: `QrScanSequence` + `ScanStep` | `…\Forms\Components`, `…\Support` |
| focus to jump from one scan field to the next | Sequential Field Scanning: `QrScanner::nextField()` | `Mmuqiitf\FilamentQrCode\Forms\Components` |
| many scans appended to one list (stocktake, receiving) | Batch Collector Scanning: `QrCollector` (form) or `QrCollectAction` (table/repeater action) | `…\Forms\Components`, `…\Tables\Actions` |
| show a QR in a form / table / infolist | `QrCodeDisplay` / `QrColumn` / `QrEntry` | `…\Forms\Components`, `…\Tables\Columns`, `…\Infolists\Components` |
| download one QR / many as a ZIP | `DownloadQrAction` / `DownloadQrBulkAction` | `Mmuqiitf\FilamentQrCode\Tables\Actions` |
| generate an SVG/PNG in PHP | `FilamentQrCode` facade or `QrCodeService` | `…\Facades`, `…\Services` |
| a Wi-Fi, vCard, mailto, SMS or geo payload | `QrPayload` | `Mmuqiitf\FilamentQrCode\Support` |

## Do

- **Write out every import**, including enums (`BarcodeFormat`, `ScanMode`, …) and Filament classes (`Filament\Schemas\Schema`). Nothing is global.
- **Set `->formats([...])`** whenever you know the symbology. It speeds up decoding and cuts false positives.
- **Check on the server** with `lookup()`, `lookupUsing()`, `validateUsing()`, `rejectWhen()` or `pipe()`. Live scans write straight into the Livewire state, and the server checks are what reject bad codes.
- **Normalize live scans** with `normalizeWith()` / `normalizeUsing()` (or `normalizeStepUsing()` on a sequence).
- **Give a `QrScanSequence` a `->statePath()`** (or `->key()`), and read it on submit with `mergeSequenceState()` / `isSequenceComplete()`.
- **Name fields exactly** in `nextField()` and `QrHardwareScannerListener::make([...])`. Dot paths like `items.0.sku` work, and `sku` never matches `parent_sku`.
- **Add the `@source` line** for the package views to the panel theme, and run `php artisan filament:assets` after installing or updating.
- **Use component setters** for per-field options. They always win over `config/qr-code.php`.

## Don't

- **Don't rely on `scanFormat()`, `onScan()` or `onStepScanned()` for live scans.** They are programmatic-only and run only through `formatScannedValue()`, `triggerOnScan()` and `triggerOnStep()`. Use `normalizeUsing()`, `onScanned()` / `onRejected()` or `afterStateUpdated()` instead.
- **Don't add your own `keydown` listener for guns.** Register custom inputs with `window.FilamentQrCode.router.register()` so each burst has exactly one owner.
- **Don't call or extend anything tagged `@internal`** (see below).
- **Don't hand-merge sequence state** with `array_merge($this->data, …)`. Use `mergeSequenceState()`.
- **Don't promise a torch button.** It only appears when the camera reports the capability, and iOS Safari usually doesn't.
- **Don't test camera scanning over plain `http`.** It needs `https` or `localhost`.
- **Don't edit `resources/dist`** in the vendor directory. It is a compiled bundle.

## Common mistakes

| Symptom | Mistake | Fix |
| --- | --- | --- |
| `Class "QrScanner" not found` | missing `use` statement | `use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;` |
| unstyled Start/Stop buttons | the theme doesn't scan the package views | add `@source '../../../../vendor/mmuqiitf/filament-qr-code/resources/views/**/*';` and rebuild |
| normalizer never runs | put on `scanFormat()` | move it to `normalizeUsing()` |
| bad codes get saved | only `scanRules()` (which run on submit) | add `lookup()` / `validateUsing()` for live rejection |
| a sequence submit misses values | reading `$this->data` directly | `$sequence->mergeSequenceState($this->form->getState())` |
| a gun scan fills a search box | the Station Listener fell back to the first empty input | `->fallbackToAnyInput(false)` and listen for `qr-scan-unrouted` |
| a slow Bluetooth gun is ignored | `min_fast_keys` is 3 | `->minFastKeys(1)` |
| a table page is slow with many QRs | turned off `lazyModal()` or the cache | keep `->lazyModal()` (the default) and `generator.cache_ttl` |

## Public API and `@internal`

Everything in the [API map](api.md) follows semver. Members tagged `@internal` are not public API and can change in any release, including patches: render specs and caches, client payloads (`toClient()`), check pipelines, and the Livewire/Filament hooks that have to stay `public`. The API map leaves them out on purpose. If a task seems to need one, look for the public setter or the extension seam in [extending.md](extending.md) instead.

## Examples with full imports

A QR Scanner Field with a server-side lookup:

```php
use App\Models\Product;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

public static function configure(Schema $schema): Schema
{
    return $schema->components([
        QrScanner::make('sku')
            ->formats([BarcodeFormat::Code128, BarcodeFormat::Ean13])
            ->normalizeWith(uppercase: true)
            ->lookup(Product::class, 'sku')
            ->onScanned(fn (string $code, $record) => logger()->info("Scanned {$code}")),
    ]);
}
```

A Station Listener routing gun scans into two fields:

```php
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

public static function configure(Schema $schema): Schema
{
    return $schema->components([
        QrHardwareScannerListener::make(['sku', 'quantity'])->autoFocusNext(),
        TextInput::make('sku'),
        TextInput::make('quantity'),
    ]);
}
```

A Scan Sequence Container with checked steps:

```php
use App\Models\Product;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

QrScanSequence::make()
    ->statePath('receipt')
    ->steps([
        ScanStep::make('po')->label('Purchase order')->validateUsing(['regex:/^PO-\d+$/']),
        ScanStep::make('sku')->lookup(Product::class, 'sku'),
    ])
    ->autoSubmit();
```

Batch Collector Scanning into a list:

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('scanned_items')
    ->formats([BarcodeFormat::QrCode])
    ->allowDuplicates(false)
    ->distinctItems();
```

Showing and downloading a QR:

```php
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

QrColumn::make('sku')->thumbnailSize(48)->previewable();

DownloadQrAction::make()->qrData(fn ($record) => $record->sku);
```

Generating in PHP:

```php
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Support\QrPayload;

$dataUri = FilamentQrCode::make()
    ->size(300)
    ->generate(QrPayload::wifi('Shop Floor', 'secret-1'))
    ->toDataUri();
```

## Where to look next

- Every public method: [api.md](api.md). Every config key: [reference.md](reference.md#configuration).
- Browser and Laravel events: [events.md](events.md). Extension seams: [extending.md](extending.md).
- Worked flows: [recipes](recipes/pos.md) (POS, goods receiving, work-order station, label page, custom inputs, styling, ZXing decoder).
