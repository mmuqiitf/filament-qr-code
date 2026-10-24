# Changelog

All notable changes to `filament-qr-code` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed
- `generator.cache_ttl => false` now turns the persistent (Laravel cache) render cache off, as documented; it used to cache forever like `null`. `QrCodeService::cacheTtl(false)` does the same at runtime.

### Added
- Public API boundary: internal classes and methods are tagged `@internal` and excluded from semver; `recordsToFiles()`, `QrColumn::flushDataUriCache()`, `normalizeScannedCode()`, `runScanChecks()` and `QrCodeService::effectiveCacheTtl()` are documented as public API.
- Swappable camera decoder: `FilamentQrCode.qrCameraCore.setDecoder(loader)`; `setDecoder(null)` restores the default.
- Native `BarcodeDetector` decoding when the browser supports every requested format, with html5-qrcode as the fallback.
- Torch and zoom controls in every viewfinder when the camera supports them; `->cameraControls(false)` / `qr-code.camera.controls` hides them.
- Recipe: ZXing decoder adapter (`docs/recipes/zxing-decoder.md`), not bundled.
- Browser smoke tests (Playwright + Testbench workbench) with a fake camera and a synthetic keyboard-wedge burst, run in CI, plus a scanner-modal test.
- The camera stops while the page is hidden (locked phone, background tab) and starts again when it is visible, unless the operator stopped it meanwhile.
- Docs: events reference (`docs/events.md`), extension points (`docs/extending.md`), a documentation table in the README, an intro to the sequence config, and Pest tests that every fired event is documented and every PHP snippet in the docs parses.
- CI runs on pushes to `main`, `0.*.x` and `1.*.x` release branches and on pull requests.

### Changed
- Internal: `QrCodeService` is now a thin facade over `Rendering\QrRenderer`, `QrRenderCache`, `QrLogoCompositor`, `QrCaptionCompositor`, `QrRenderOptions` and `HexColor`; ZIP packing moved to `Rendering\QrZipArchiver` (`Support\QrZipArchive` still works). Output is byte-identical and the public API is unchanged. The new `Rendering\*` classes are `@internal`.
- Breaking for subclasses of `QrCodeService` only: the protected `applyLogo()`, `applyTextOverlay()`, `readPersistentCache()` and `writePersistentCache()` methods and the protected static `$renderCache` property were removed (no shims). Override `generate()` or wrap the service instead.
- Breaking: `hardware_scanner.min_fast_keys` defaults to 3 (was 1), so fast typing is not taken for a scan. `->minFastKeys(1)` restores the old behaviour.
- Tests: Pest pins the array cache store, so a Testbench skeleton `.env` left by `workbench:build` no longer breaks the suite.

### Removed
- Breaking: the `filament-qr-code.image` route alias; use `filamentQrCode.image`.
- Breaking: `suppressWhenGlobalListener()` / `isSuppressedWhenGlobalListenerActive()`, the `suppressWhenGlobalListenerActive` argument of `hardwareScanner()`, and `FilamentQrCode.isGlobalHardwareListenerActive()`; the input router already gives each burst one owner.
- Breaking: `QrScanSequence::statePathPrefix()`, its mismatch warning and the `ui.sequence_prefix_mismatch` translation; flat step paths follow the schema `statePath()`.
- Breaking: the `LogQrCodeScan` listener alias; use `LogQrCodeScanListener`.
- Breaking: `QrCodeDisplay::getQrSvgOrDataUri()`; use `getQrDataUri()` (also in published `qr-code-display` views).

### Fixed
- `repeat(n)` finished early with `finishWith()` now runs `validateAllUsing()`, rejecting an invalid set like completion does.
- `pipe()` stages and step checks see the same `$values` on submit as live (the steps before, plus earlier codes in a repeat step).
- Scanner modal: the overlay is positioned by the package stylesheet, so it covers the viewport even when the theme does not `@source` the package views.

## [0.6.1] - 2026-10-24

### Fixed
- `QrCollectAction` (#35): every scan called `$wire.handleCollectorScan()` even when the host Livewire component had no such method (Livewire's `$wire` proxy answers every name), raising `MethodNotFoundException` and never firing `qr-collector-item-added`. The server now tells the browser whether the hook exists. Same fix for the `QrCollector` field.
- `QrCollectAction` (#35): the modal now starts the camera on open like the `QrScanner` modal, waits for the viewfinder to be laid out (an opening modal left a zero-sized feed), and shows the shared loading / error overlay with Retry instead of a silent black viewfinder. Start no longer does nothing before the camera list loads.
- The `QrCollector` field shows the same loading / error overlay. The overlay is one shared partial, `components/camera-status.blade.php`.
- Camera left running after close: closing the `QrScanner` modal, the `QrCollectAction` modal, or removing a collector / scan sequence (Livewire re-render or navigation) while the camera was still opening left the stream (and the camera light) on. Every stop now cancels an in-flight start, which stops its own stream when it resolves.
- `QrScanSequence`: Start no longer does nothing before the camera list loads, and camera errors show the shared loading / error overlay with Retry (they were set but never displayed).
- Docs: README section on adding the package views to the panel theme's Tailwind `@source` list; without it the scan UI renders unstyled.

## [0.6.0] - 2026-10-22

### Upgrading from 0.5
See [UPGRADE.md](UPGRADE.md). In short: run `php artisan filament:assets`, republish customised views,
use `ScanResult::recordKey()` instead of `$record->getKey()`, and move off the deprecated `filament-qr-code.image`
route name (removed in 1.0).

### Added
- **Runtime control (P2.1, #33).** Scanners and sequences pick up server-side option changes on every Livewire re-render
  without being re-created; the scan in progress finishes first. Config `scan.sync_options` (`'morph'`) and
  `syncOptions(OptionsSync::Refresh)` switch to explicit `qr-scanner-refresh` / `qr-sequence-refresh` events.
- Per-element API `element.qrScanner` / `element.qrScanSequence` (`feed`, `pause`, `resume`, `reset`, `setOptions`,
  `refresh`, `getOptions`, `getState`, plus `goToStep` on sequences) and window events aimed at one field
  (`qr-scanner-feed|pause|resume|reset|refresh|set-options`, `qr-sequence-feed|go-to|reset|pause|resume|refresh|set-options|set-repeat`).
- The sequence browser state follows the server: repeat `k / n`, labels, active steps and values.
- `QrScanSequence::goToStep()`, `resetSequence()` and `setRepeatCount()` for page code.
- `ScanStep::when(fn (array $values) => ...)`: conditional steps are skipped, not required, and their value is dropped.
- `window.FilamentQrCode.router` (the shared hardware input router) and `createInputRouter` are public and documented.

- **Extension points (P2.2, #34).** See `docs/customizing.md`.
  - `lookupUsing(fn ($code) => ...)` on scanners and steps replaces the model lookup (`fallback: true` runs it after a miss). `lookup()` model and column accept closures.
  - `normalizeWith(trim, uppercase, stripPrefix)` presets apply in the browser and on the server. `normalizeUsing()` now runs on the server before rules and lookup. `ScanStep::normalizeUsing()` added.
  - `->pipe(Stage::class)` with the `ScanCheckStage` contract runs custom checks after the built-in ones (container-resolved; also on submit).
  - `ScanStep` closures get `$get`/`$livewire`, `$step` and `$values`. `ScanStep` is macroable.
  - `onStepAccepted()` / `onStepRejected()` side-effect hooks on sequences.
  - `ScanAccepted` / `ScanRejected` gain `statePath`, `livewireClass`, `livewireId`, `sequence` and `values`. `ScanSequenceCompleted` gains `livewireClass` and `livewireId`. The `QrCodeScanned` context gains `state_path`.
  - Views are split into publishable sub-views. They add `fi-qr-*` classes, `data-qr-part` markers, `extraAttributes()` on the roots, `QrCodeRenderHook` render hooks and `--fi-qr-*` CSS variables. The rendered output is otherwise unchanged.
  - `php artisan make:qr-scan-field Name [--sequence] [--force]`, with stubs published via `--tag=filament-qr-code-stubs`.

### Docs
- **Examples and recipes (P2.3, #36).** Recipes in `docs/recipes`: `pos` (updated: no `wire:key` re-mount, `normalizeWith()`,
  pause/resume buttons), `warehouse`, `manufacturing`, `generator-page`, `custom-input` and `styling`. Every PHP block is
  linted and checked against the real API by `tests/Feature/RecipesTest.php`.
- `UPGRADE.md` with the 0.5 to 0.6 steps.
- `filament-qr-code.image` is documented as a deprecated alias of `filamentQrCode.image`, removed in 1.0.

### Changed
- `ScanResult::$record` is typed `mixed` (lookups may return arrays or objects). Use `recordKey()` for the key.
- `normalizeUsing()` on a scanner now also normalizes server-checked scans before the checks, not only the stored state.
- An optional step whose check fails now also gives way to a following repeat step, so `po, sku, lot?, serials` works.
- `QrScanSequence::make()` runs `setUp()` and `configureUsing()` callbacks; sequences with a state path now fill to `[]`.
- The scanner's `x-data` no longer embeds the options (they're on `data-qr-options`), and the sequence view gained a
  wrapper `<div data-qr-options>`. Re-publish customised views. Run `php artisan filament:assets` after updating.

## [0.5.0] - 2026-10-19

### Upgrading from 0.4
- Run `php artisan filament:assets` after updating: the scanner and sequence JS changed (input router,
  continuous mode, sequence server path).
- No breaking API changes. Fields and sequences that don't use the new options behave as before: plain
  `fields()` sequences make no request, and `hardwareInput()` defaults to `'focus'`, today's behaviour.
- Replace the deprecated `filament-qr-code.image` route name with `filamentQrCode.image`.

### Added
- **Input router (P1 part 2).** One page-wide hardware input router: every keyboard-wedge burst gets exactly one
  owner (open camera modal, then the focused scan field, then the first listening field on the page). A field that
  can't take a burst (busy, too short) passes it on to the next one (#28, #29).
  - `QrScanner::hardwareInput(scope: HardwareInputScope::Focus|Page)` and config `hardware_scanner.default_scope`
    (`'focus'`).
  - `qr-scan-routed` browser event; `data-qr-ignore` keeps wedge text in an input.
- **Continuous mode (P1 part 2).** `QrScanner::mode(ScanMode::Continuous)`,
  `continuousDisplay(ContinuousDisplay::Empty|LastCode)`, `pauseOnReject()` and `keepCameraOpen()`, with defaults in
  config `scan.continuous` (`display` `'empty'`, `pause_on_reject` `false`, `keep_camera_open` `false`,
  `cooldown_ms` `300`). `onScanned()` runs once per accepted code (#30).
  - One-slot queue for scans that arrive while a continuous field is checking; extra scans fire `qr-scan-dropped`.
    New `qr-scan-paused` and `qr-scan-resumed` events, and a `queued` reason on `qr-scan-routed`.
  - `mode` (`'single'` or `'continuous'`) in `qr-scanned`, `qr-scan-accepted`, `qr-scan-rejected` and
    `qr-scan-duplicate`.
  - `required()` on a continuous field logs a warning once in the local environment.
  - POS recipe: `docs/recipes/pos.md`.
- **Sequence steps (P1 part 3).** `ScanStep` and `QrScanSequence::steps()` with per-step `validateUsing()`,
  `lookup()` and `optional()` (an optional step gives way when the code fits the next step). Checked sequences make
  one `handleStepScan` request per scan (#31).
  - Whole-set check `validateAllUsing()` and `onInvalidSequence(InvalidSequenceBehavior::JumpToStep|Restart)`;
    `onCompleted()`, `autoSubmit()`, `resetAfterSubmit()` and `continuous()`, with defaults in config `sequence`.
  - New `ScanSequenceCompleted` event and `qr-sequence-rejected` / `qr-sequence-invalid` browser events.
  - Form submits re-run step checks and `validateAllUsing()`. Typed corrections are checked as `ScanInput::Manual`
    (new `'manual'` source). A checked sequence without `statePath()`/`key()` gets an automatic key.
- **Wedge-driven sequences (P1 part 3).** Hardware bursts fill and advance sequence steps without focus; a focused
  step input takes the burst for that step; one burst waits during a server check. `qr-sequence-step` gains
  `source` (#32).
  - `ScanStep::sticky()` keeps a value across `resetAfterSubmit()`, `continuous()` and `Restart` rounds, with a Clear
    button.
  - `ScanStep::repeat(?n)`, `onRepeatComplete(RepeatComplete::Advance|Wait)`, `finishWith()` and
    `allowDuplicates()`, with config `sequence.repeat_complete` (`'advance'`) and `sequence.finish_code` (`null`),
    a Done control and remove buttons on collected codes. A failing `repeat()` count closure falls back to an
    open-ended step.
- Delivered in #26–#32 (P1b and P1c specs, plans and PRs).

### Changed
- The signed QR image route is now named `filamentQrCode.image` (camelCase) (#27).

### Fixed
- A burst is no longer handled twice when several hardware consumers share a page (for example two collectors, or a
  `QrScanner` with `suppressWhenGlobalListener(false)` next to a listener).

### Deprecated
- The `filament-qr-code.image` route name still resolves to the same URL but is deprecated and will be removed in
  1.0. Replace `route('filament-qr-code.image')` / `URL::signedRoute('filament-qr-code.image', ...)` with
  `filamentQrCode.image`.
- `suppressWhenGlobalListener()`, `isSuppressedWhenGlobalListenerActive()` and the JS
  `isGlobalHardwareListenerActive()` no longer affect routing; removal planned for 1.0.

## [0.4.0] - 2026-10-17

### Upgrading from 0.3
- Run `php artisan filament:assets` after updating: the scanner JS and CSS
  changed (scan pipeline, outcome feedback, flash styles).
- No breaking API changes. `QrScanner` fields without server checks behave as
  before; `vibrate()` keeps its signature and gains optional `success:` /
  `error:` patterns.

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
  - Delivered in #14–#22 (spec, plans and P1a PRs 1–5).
- Config: `qr-code.scan.cooldown_ms`, `qr-code.scan.clear_on_reject`,
  `qr-code.feedback.success`, `qr-code.feedback.error`.

### Fixed
- Upload scans flashed (and named the field in `qr-scan-*` events as) the
  hidden file input instead of the field: Alpine's `$el` is the calling
  element, so `QrScanner` now resolves the field wrapper via `$root` (#24).
- The outcome flash ring was never visible: it played behind the open
  scanner modal and faded in 300 ms. It now runs on the field after the modal
  closes (on the modal panel while it stays open) and lasts 900 ms (#23).

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

[Unreleased]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.6.1...HEAD
[0.6.1]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.6.0...v0.6.1
[0.6.0]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.3.1...v0.4.0
[0.3.1]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/mmuqiitf/filament-qr-code/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/mmuqiitf/filament-qr-code/releases/tag/v0.1.0
