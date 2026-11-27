---
name: filament-qr-code-generating
description: Show or download QR codes and barcodes in Filament v5 with mmuqiitf/filament-qr-code. Use for QrCodeDisplay, QrColumn, BarcodeDisplay, BarcodeColumn, and download actions.
---

# Generating

Read `vendor/mmuqiitf/filament-qr-code/docs/generating.md` and `docs/barcodes.md`.

- Form and infolist images embed a data URI. Table barcode cells use the signed route unless `->embedded()`.
- A live product preview binds `BarcodeDisplay::data()` to the SKU field being edited, as on the workbench `product-edit` page.
- `FilamentQrCode::barcode()` and `QrCodeService` are the PHP entry points outside a panel.
