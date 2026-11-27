---
name: filament-qr-code-printing
description: Print QR and barcode label sheets from a Filament v5 table with mmuqiitf/filament-qr-code. Use for PrintLabelsAction, Avery layouts, and GS1 Digital Link labels.
---

# Printing

Read `vendor/mmuqiitf/filament-qr-code/docs/printing.md`.

- `PrintLabelsAction` and `PrintLabelsBulkAction` open an HTML sheet. There is no required PDF library.
- `LabelSheet::avery5160()` is the default. `digitalLink()` fills the QR. `barcodeData()` adds a 2D or 1D symbol beside it.
- Run `php artisan filament:assets` so the print stylesheet is published.
