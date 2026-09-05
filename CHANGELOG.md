# Changelog

All notable changes to `filament-qr-code` will be documented in this file.

## 1.0.0 - 2026-09-05

### Added
- Initial release for Filament v5.
- `QrScanner` form field with camera scan modal, rear camera preference, torch toggle, and image upload fallback.
- Sequential field chaining via `->nextField('...')`.
- `QrScanSequence` split-screen dashboard orchestrator container.
- Hardware keyboard wedge scanner support with burst detection (<50ms) and premature form submit prevention.
- `QrCollector` and `QrCollectAction` for batch continuous scanning with duplicate prevention.
- Full QR generator suite (`QrCodeService`, `QrCodeDisplay`, `QrColumn`, `QrEntry`, `DownloadQrAction`).
- Sensory feedback (synthesized Web Audio API tone and haptic vibration).
- Self-contained JS bundle compiling `html5-qrcode` via Vite without CDN dependency.
- PHPStan Level 9 strict typing and Pest v3 test suite.
