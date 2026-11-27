---
name: filament-qr-code-scanning
description: Add a Filament v5 camera scanner, station listener, or scan sequence with mmuqiitf/filament-qr-code. Use for QrScanner, QrHardwareScannerListener, QrScanSequence, GS1 label scans, and offline or rejection behaviour.
---

# Scanning

Read `vendor/mmuqiitf/filament-qr-code/docs/scanning.md` and `docs/components.md`.

- `QrScanner` for one field. `QrHardwareScannerListener` for a gun station. `QrScanSequence` plus `ScanStep` for ordered fields.
- Set `->formats([...])` or `->preset(ScannerPreset::...)` when the symbology is known. `ScannerPreset::Gs1` does not parse labels; call `->gs1()`.
- Server checks (`gs1()`, `rejectWhen()`, `rules()`, `lookup()`) reject a live scan before state is written.
- `->blockWhenOffline()` rejects new scans while the browser is offline. The default is a banner only.
- `php artisan qr-code:fake-scan {code} --gs1` prints the accept or reject reason without a camera.
