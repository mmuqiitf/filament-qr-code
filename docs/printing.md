# Label printing

Print a grid of labels from a Filament table with `PrintLabelsAction` (one row) or `PrintLabelsBulkAction` (the selected rows). The package renders HTML and a CSS print stylesheet. There is no required PDF library; a [dompdf recipe](recipes/print-pdf.md) shows how to turn the same HTML into a PDF if you need a file.

Each label can carry a QR code, a barcode, or both. The QR can be a GS1 Digital Link URI; the barcode can be any [supported type](barcodes.md), including Data Matrix, GS1 DataMatrix and PDF417.

Run `php artisan filament:assets` after upgrading so the print stylesheet is published.

## Table actions

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheet;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsBulkAction;

$table
    ->recordActions([
        PrintLabelsAction::make()
            ->sheet(LabelSheet::avery5160())
            ->digitalLink(fn ($record) => ['01' => $record->gtin, '10' => $record->lot])
            ->barcodeData(fn ($record) => $record->element_string, BarcodeType::Gs1DataMatrix)
            ->heading(fn ($record) => $record->sku)
            ->caption(fn ($record) => $record->name),
    ])
    ->toolbarActions([
        PrintLabelsBulkAction::make()
            ->sheet(LabelSheet::avery5160())
            ->digitalLink(fn ($record) => ['01' => $record->gtin, '10' => $record->lot])
            ->barcodeData(fn ($record) => $record->element_string, BarcodeType::Gs1DataMatrix)
            ->heading(fn ($record) => $record->sku)
            ->caption(fn ($record) => $record->name)
            ->copies(1)
            ->offset(0),
    ]);
```

The action opens a print-ready page in a new tab. Use the browser Print dialog (or the Print button on that page) and choose the matching paper size (US Letter for Avery 5160 / 5163, A4 for L7160). The label sheet itself stays light so codes remain scannable; the on-screen Print toolbar follows `prefers-color-scheme`.

Rows with no QR or barcode data are skipped. If every selected row is empty, a warning notification is shown and no tab opens.

If you do not call `digitalLink()` or `qrData()`, the QR payload is taken from the record's `qr_code`, `code` or `sku` attribute, matching `DownloadQrAction`. `barcodeData()` is only added when you set it.

## Sheets

| Preset | Paper | Layout | Label size |
| --- | --- | --- | --- |
| `LabelSheet::avery5160()` (the action default) | US Letter | 3 × 10 | 2.625 in × 1 in |
| `LabelSheet::avery5163()` | US Letter | 2 × 5 | 4 in × 2 in |
| `LabelSheet::l7160()` | A4 | 3 × 7 | 63.5 mm × 38.1 mm |
| `LabelSheet::threeAcross()` | any | flowing 3 columns | auto, same grid as the older `.filament-qr-label-sheet` CSS |

A custom sheet is `LabelSheet::make(columns: 2, rows: 4, labelWidth: '80mm', labelHeight: '40mm', pageSize: 'A4', marginTop: '10mm', ...)`. Dimensions are CSS lengths.

`->offset(3)` skips the first three cells on the first page, so a partly used Avery sheet can be finished. `->copies(2)` prints each record twice.

## Per-record layout

`labelUsing()` replaces the default QR / barcode / caption builders when a record needs a one-off layout:

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Printing\Label;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsAction;

PrintLabelsAction::make()
    ->labelUsing(fn ($record) => Label::make()
        ->digitalLink(['01' => $record->gtin, '10' => $record->lot])
        ->barcode($record->gtin, BarcodeType::DataMatrix)
        ->heading($record->sku)
        ->caption($record->name));
```

You can also build HTML yourself with `LabelSheetRenderer::make($sheet)->toHtml($labels)` or `toDocument($labels)` (a complete page with the print CSS inlined). The print route keeps that document in the cache for 30 minutes behind an unguessable token.

## Hand-built pages

The CSS classes `.filament-qr-label-sheet`, `.filament-qr-label` and `.filament-qr-no-print` still work on a custom Filament page: in print, camera UI is hidden and a sheet without `data-fq-sheet` tiles three labels per row. See the [label generator recipe](recipes/generator-page.md).
