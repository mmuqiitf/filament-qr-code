# Changelog

All notable changes to `filament-qr-code` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Server-side checks for live `QrScanner` scans (P1a). Each scan makes one
  Livewire call to a new `handleScan()` hook (Filament's
  `callSchemaComponentMethod`) and gets a pass/fail result back.
  - `validateUsing()` accepts Laravel rule arrays, rule objects and closures;
    `lookup(Model::class, column, modifyQueryUsing:)` finds a record. Checks
    run in order (rules, lookup, closures) and stop at the first failure, with
    translated messages (`record_not_found`, `scan_failed`). The same checks
    re-run on form submit (`ScanChecksRule`).
  - `onScanned()` / `onRejected()` callbacks with `ScanResult`, `Get` and `Set`
    (live scans only); `clearOnReject()`; events `ScanAccepted` and
    `ScanRejected`. Accepted live scans now fire `QrCodeScanned` (audited when
    `qr-code.audit.enabled`).
  - Browser pipeline: duplicate suppression within `cooldown()` (event
    `qr-scan-duplicate`), the camera pauses while a check runs, the field
    clears on reject and `qr-scan-rejected` / `qr-scan-accepted` fire once.
    Page-level hardware bursts are checked too.
  - Outcome feedback: success and error tones, vibration patterns and a
    green/red flash, configurable in `qr-code.feedback.success` / `.error` and
    per field with `beep()`, `vibrate(success:, error:)` and `flash()`.
- Config: `qr-code.scan.cooldown_ms`, `qr-code.scan.clear_on_reject`,
  `qr-code.feedback.success`, `qr-code.feedback.error`.

### Changed
- `QrScanner` fields without any server check behave exactly as before
  (no extra request, no cooldown, single success beep).

## [0.3.1] - 2026-10-16

### Fixed
- `QrHardwareScannerListener` routed bursts into whichever text input had
  focus (e.g. a POS member/search box) even when fields were registered.
  A focused input now only receives the scan if it is a registered field;
  otherwise the scan goes to the registered field and the focused input is
  restored to its pre-burst value (with `input`/`change` events so
  Livewire state is corrected too). Without registered fields the focused
  input still receives the scan.

## [0.3.0] - 2026-10-15

### Upgrading from 0.2
- Run `php artisan filament:assets` after updating: the camera decoder is
  now a separate published file (`filament-qr-code-decoder.js`).
- `QrScanner` no longer sends a Livewire request per keystroke. If you rely
  on `rejectWhen()` / `normalizeUsing()` for typed (not scanned) input, add
  `->live(onBlur: true)` to the field.

### Security
- Add `SECURITY.md` with private reporting through GitHub Security Advisories.
- Add Dependabot for Composer, npm, and GitHub Actions (weekly, 7-day
  cooldown); it also keeps the SHA-pinned actions current.
- Exclude development-only files (tests, CI, tooling configs, agent files)
  from the Composer dist archive via `.gitattributes`.
- Pin every third-party GitHub Action in CI to a full commit SHA
  (`actions/checkout` v4.4.0, `actions/setup-node` v4.4.0,
  `shivammathur/setup-php` 2.40.0), so a moved tag cannot change what
  runs in CI. A test now fails on any unpinned `uses:` reference.

### Added
- `hardware_scanner.min_fast_keys` config, `->minFastKeys()` and
  `hardwareScanner(minFastKeys:)`: consecutive fast gaps required before a
  terminator counts as a scan (default `1` keeps 0.2 behaviour).
- `hardware_scanner.fallback_to_any_input` config and
  `QrHardwareScannerListener::fallbackToAnyInput()` (default `true`).
- `qr-scan-unrouted` window event when a page-global burst finds no target.
- `getComponentScanPayload()` extension point and `Support\ConfigValue`.
- `QrCodeDisplay::caption()` accepts a Closure.
- `getDownloadDataUri()` on QrCodeDisplay/QrEntry (download with caption).

### Changed
- Vite 8: the camera decoder is built as a self-contained sibling
  (`filament-qr-code-decoder.js`) and registered with Filament as
  `loadedOnRequest`, so `filament:assets` publishes it next to the main
  script.
- QrCodeDisplay/QrEntry show the caption once, as HTML under the preview:
  `getQrDataUri()` no longer draws it into the image (an SVG preview stays
  SVG). The download link uses `getDownloadDataUri()`, which still
  includes the caption.
- Field lookup is now exact (name, id, `data-field-name`, or a
  `wire:model` path equal to / ending in `.<name>`); substring fallbacks
  and Livewire 2 `wire:model.defer` lookups were removed.
- Keyboard auto-repeat (held-down keys) is always ignored by hardware-scanner
  detection.

### Fixed
- `filament:assets` did not publish the lazy decoder chunk, so camera
  scanning and image upload could fail with "Failed to load camera decoder"
  (404) on fresh installs.
- Scan from image file failed on every device with a camera: html5-qrcode
  refuses a file scan while the camera streams. The camera now pauses for
  the decode and resumes if the image has no code; the same file can be
  picked again, and the failure message is translated (`ui.file_scan_failed`).
- Long QrCodeDisplay/QrEntry captions were cut off in the rendered image
  (GD and Imagick); the canvas now widens to fit and centres the code.
- `QrScanner` sent a Livewire request on every typed character and wrote
  each scan twice; it now writes once per scan and leaves typing to
  `wire:model`.
- Held-down keys (auto-repeat) followed by Enter were routed as scans.
- Field lookup matched substrings (`sku` focused `parent_sku`), failed on
  nested paths (`items.0.sku`), and ignored `wire:model.blur` /
  `.live.debounce.*` modifiers.
- `QrCollector::distinctItems()` evaluated its Closure before the form was
  mounted; it is now evaluated at validation time.

## [0.2.0] - 2026-10-11

### Added
- `HasScanPayload::getScanPayload()`: one browser payload behind every scan
  surface (`QrScanner`, `QrScanSequence`, `QrCollector`,
  `QrHardwareScannerListener`, `QrCollectAction`).
- `Support\QrRenderSpec`: one render spec behind Display, Entry, table
  column (thumbnail, eager and lazy signed preview), and both download
  actions — signed previews now reproduce logo and caption exactly.
- Download options `qrColor()`, `qrBackgroundColor()`,
  `qrErrorCorrection()`, `qrLogo()`, `qrCaption()` on single and bulk
  actions (defaults stay black on white).
- Vitest suite (`npm test`) for interceptor routing, sanitize parity, the
  single-beep invariant, and the single collector notify channel.

### Fixed
- `config/qr-code.php` `camera.*`, `hardware_scanner.*`, and
  `feedback.sound`/`feedback.vibrate` values now reach the browser;
  explicit component setters still win.
- A handheld-scanner hit on a field no longer beeps twice: the
  interceptor routes without feedback and each delivery point beeps once
  (the Station Listener beeps only when a target field is filled).
- Batch Collector scans notify through exactly one channel (Livewire hook
  when defined, else the `qr-collector-item-added` event) instead of both.
- Bulk ZIP filenames use `.png` when a logo or caption forces
  rasterization, matching the file bytes.

### Changed
- Upgraded Vite 6 to 7 (Vitest stays at the registry-newest 5.0.3).

## [0.1.1] - 2026-10-10

### Fixed
- README banner is wrapped in an `<img class="filament-hidden">` tag so the
  filamentphp.com plugin page no longer duplicates the uploaded cover image,
  while GitHub still renders it.

### Security
- Bumped npm dev dependencies flagged by Dependabot: `vite` 6.4.4,
  `esbuild` 0.25.12, and `source-map-js` 1.2.2. Rebuilt `resources/dist`
  assets.

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

[Unreleased]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.3.1...HEAD
[0.3.1]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/mmuqiitf/filament-qr-code/releases/tag/v0.1.0
