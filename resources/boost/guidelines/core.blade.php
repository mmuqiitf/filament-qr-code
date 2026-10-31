## Filament QR & Barcode

`mmuqiitf/filament-qr-code` adds QR/barcode scanning (camera, image upload, keyboard-wedge Hardware Scanners) and QR generation to Filament v5. Always import classes with full `use` statements; nothing is global. Full docs: `vendor/mmuqiitf/filament-qr-code/docs/ai.md` and `docs/api.md`.

### Pick the component

- One input scanned by camera, upload, typing or gun: `Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner` (QR Scanner Field).
- A gun driving several fields with no camera UI: `Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener` (Station Listener).
- One camera filling many fields in order: `Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence` with `Mmuqiitf\FilamentQrCode\Support\ScanStep` (Scan Sequence Container).
- Many scans appended to one list: `Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector` or `Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction` (Batch Collector Scanning).
- Showing QR codes: `QrCodeDisplay` (forms), `QrColumn` (tables), `QrEntry` (infolists), `DownloadQrAction` / `DownloadQrBulkAction`; in PHP the `Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode` facade and `Mmuqiitf\FilamentQrCode\Support\QrPayload`.

### Rules

- Set `->formats([...])` with `Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat` cases when the symbology is known.
- Reject bad live scans on the server with `lookup()`, `lookupUsing()`, `validateUsing()`, `rejectWhen()` or `pipe()`; `scanRules()` only run on submit.
- `scanFormat()`, `onScan()` and `onStepScanned()` never run on live scans (programmatic only). Normalize with `normalizeWith()` / `normalizeUsing()` and react with `onScanned()` / `onRejected()`.
- Give `QrScanSequence` a `->statePath()` and read it with `mergeSequenceState()`; never hand-merge `$this->data`.
- Field names in `nextField()` and `QrHardwareScannerListener::make([...])` must match exactly (dot paths allowed).
- Never call or extend members tagged `@internal`; they are outside semver.
- The panel theme must `@source` `vendor/mmuqiitf/filament-qr-code/resources/views/**/*`, and `php artisan filament:assets` must run after install or update. Camera scanning needs `https` or `localhost`. `php artisan qr-code:doctor` checks the install.

@verbatim
<code-snippet name="QR Scanner Field with a server-side lookup" lang="php">
use App\Models\Product;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

QrScanner::make('sku')
    ->formats([BarcodeFormat::Code128, BarcodeFormat::Ean13])
    ->normalizeWith(uppercase: true)
    ->lookup(Product::class, 'sku')
    ->onScanned(fn (string $code, $record) => logger()->info("Scanned {$code}"));
</code-snippet>

<code-snippet name="Station Listener routing gun scans into fields" lang="php">
use Filament\Forms\Components\TextInput;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

$components = [
    QrHardwareScannerListener::make(['sku', 'quantity'])->autoFocusNext(),
    TextInput::make('sku'),
    TextInput::make('quantity'),
];
</code-snippet>

<code-snippet name="Scan Sequence Container with checked steps" lang="php">
use App\Models\Product;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

QrScanSequence::make()
    ->statePath('receipt')
    ->steps([
        ScanStep::make('po')->validateUsing(['regex:/^PO-\d+$/']),
        ScanStep::make('sku')->lookup(Product::class, 'sku'),
    ])
    ->autoSubmit();
</code-snippet>

<code-snippet name="Showing and generating QR codes" lang="php">
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

QrColumn::make('sku')->thumbnailSize(48)->previewable();

$dataUri = FilamentQrCode::make()->size(300)->generate('https://example.com')->toDataUri();
</code-snippet>

<code-snippet name="Hardening the signed image route (opt-in, off by default)" lang="php">
// config/qr-code.php
'generator' => ['logo_base_paths' => [storage_path('app/qr-logos')]],
'image_route' => ['ttl' => 60, 'throttle' => '60,1', 'strict_colors' => true],
</code-snippet>
@endverbatim
