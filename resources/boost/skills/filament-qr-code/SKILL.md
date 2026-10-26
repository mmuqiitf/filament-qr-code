---
name: filament-qr-code
description: Build QR and barcode scanning or QR generation into Filament v5 forms, tables and infolists with mmuqiitf/filament-qr-code. Use for camera scanners, handheld gun stations, scan sequences, batch collectors, QR columns and downloads.
---

# Filament QR Code

## When to use

Use this skill when a task scans QR codes or barcodes in a Filament panel, through a camera, image upload or a USB/Bluetooth gun in keyboard mode, or when it shows or downloads QR codes. Read `vendor/mmuqiitf/filament-qr-code/docs/ai.md` for the full picker and rules, and `docs/api.md` for every public method.

## Steps

1. Pick the component: `QrScanner` for one field, `QrHardwareScannerListener` for a gun-driven station, `QrScanSequence` + `ScanStep` for one camera filling fields in order, `QrCollector` / `QrCollectAction` for lists, and `QrCodeDisplay` / `QrColumn` / `QrEntry` / `DownloadQrAction` for output.
2. Write out the full `use` imports for every package class and enum.
3. Set `->formats([...])` when the symbology is known.
4. Add server checks (`lookup()`, `validateUsing()`, `rejectWhen()`, `pipe()`) so bad live scans are rejected, not only checked on submit.
5. Normalize with `normalizeWith()` / `normalizeUsing()`. `scanFormat()` and `onScan()` are programmatic only.
6. Make sure the panel theme `@source`s the package views and `php artisan filament:assets` has run. If anything is off, run `php artisan qr-code:doctor`.

## Example

<code-snippet name="Checked scanner and sequence" lang="php">
use App\Models\Product;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

QrScanner::make('sku')
    ->formats([BarcodeFormat::Code128])
    ->lookup(Product::class, 'sku');

QrScanSequence::make()
    ->statePath('receipt')
    ->steps([
        ScanStep::make('po')->validateUsing(['regex:/^PO-\d+$/']),
        ScanStep::make('sku')->lookup(Product::class, 'sku'),
    ]);
</code-snippet>

## Avoid

- Calling or extending `@internal` members.
- Custom `keydown` handlers for guns. Use `window.FilamentQrCode.router.register()`.
- Hand-merging sequence state. Use `mergeSequenceState()`.
- Promising a torch button. It only shows when the camera reports the capability.
