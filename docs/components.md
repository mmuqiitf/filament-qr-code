# Components

Each public surface, the guide that owns it, and a screenshot when the workbench has one. Per-component Boost skills live next to the umbrella skill in `resources/boost/skills/`.

| Component | Guide | Screenshot |
| --- | --- | --- |
| `QrScanner` | [Scanning](scanning.md) | [scanner-modal-camera.png](images/scanner-modal-camera.png), [server-checks-rejected.png](images/server-checks-rejected.png) |
| `QrHardwareScannerListener` | [Scanning](scanning.md) | [cashier-station-listener.png](images/cashier-station-listener.png) |
| `QrScanSequence` | [Scanning](scanning.md) | [work-order-sequence.png](images/work-order-sequence.png) |
| `QrCollector` | [Collecting](collecting.md) | [shelf-audit-collector.png](images/shelf-audit-collector.png) |
| `QrCollectAction` | [Collecting](collecting.md) | [shelf-audit-recorded.png](images/shelf-audit-recorded.png) |
| `QrCodeDisplay`, `QrColumn`, `QrEntry` | [Generating](generating.md) | [products-table-qr-column.png](images/products-table-qr-column.png), [products-qr-preview-modal.png](images/products-qr-preview-modal.png) |
| `BarcodeDisplay`, `BarcodeColumn`, `BarcodeEntry` | [Barcodes](barcodes.md) | [qr-customizer.png](images/qr-customizer.png) |
| `PrintLabelsAction` | [Label printing](printing.md) | [qr-labels-print-sheet.png](images/qr-labels-print-sheet.png) |
| `DownloadQrAction` | [Generating](generating.md) | [products-bulk-zip-export.png](images/products-bulk-zip-export.png) |
| Product edit preview | workbench `product-edit` | live `BarcodeDisplay` of the SKU being typed |

Boost skills: `filament-qr-code` (picker), `filament-qr-code-scanning`, `filament-qr-code-collecting`, `filament-qr-code-generating`, `filament-qr-code-printing`.
