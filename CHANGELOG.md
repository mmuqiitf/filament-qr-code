# Changelog

All notable changes to `filament-qr-code` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-10-10

First tagged release for Filament v5. Previous `1.0.0` heading was never
tagged or published; version history starts here.

### Added
- `QrScanner` form field with camera scan modal, rear-camera preference,
  image upload fallback, and handheld-scanner integration.
- `QrScanSequence` container: one shared camera feed walking multiple fields
  in order, with editable steps and `statePath()`-bound state readable via
  `mergeSequenceState()`, `isSequenceComplete()`, and `getMissingSequenceKeys()`.
- `QrHardwareScannerListener` page-global interceptor plus field-level burst
  handling (burst detection, terminator-less flush via `scanTimeoutMs`,
  STX/ETX/CR/LF sanitization, stand-down beside a global listener).
- Live value normalization (`normalizeUsing()`, `normalizeStepUsing()`),
  kept separate from the programmatic-only `scanFormat()` / `onScan()` hooks.
- Submit-time `scanRules()` with instant `rejectWhen()` feedback through the
  `qr-scan-rejected` event.
- Batch collection (`QrCollector`, `QrCollectAction`) with a `distinctItems()`
  server-side duplicate guard.
- Full QR generator suite (`QrCodeService`, `QrCodeDisplay`, `QrColumn`,
  `QrEntry`, `DownloadQrAction`, `DownloadQrBulkAction` ZIP export with a
  print stylesheet).
- `QrPayload` builders for WiFi, vCard, mailto, SMS, and geo strings.
- `QrCodeScanned` audit event with an opt-in log listener (`audit` config).
- `qr-code:doctor` Artisan command and troubleshooting guide.
- Lazy decoder chunk: ~20K global bundle, `html5-qrcode` downloads once on
  first camera use; lazy signed-route table previews backed by a persistent
  render cache.
- English and Indonesian (`id`) translations.
- Sensory feedback (Web Audio tone and haptic vibration) with
  assistive-tech labels on scanner modals.
- PHPStan level 9 and Pest test suite.

### Changed
- Handheld-scanner vocabulary unified as "Hardware Scanner".
- Unrestricted symbologies auto-degrade effective fps 25 → 12 unless `fps()`
  is set explicitly.

### Removed
- Torch toggle and zoom controls from the camera modal.

### Fixed
- Assets re-register on every application boot (later boots in test suites
  and Octane workers previously served pages without the package bundle).
- Missing font files fall back to GD bitmap fonts instead of throwing.

[Unreleased]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/mmuqiitf/filament-qr-code/releases/tag/v0.1.0
