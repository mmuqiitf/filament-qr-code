# AGENTS.md

Filament v5 + Laravel 11/12/13 QR package (`mmuqiitf/filament-qr-code`). PHP `^8.2`, local PHP `8.5`.

## Commands

- `composer test` / `vendor/bin/pest` — full suite (Pest 3 + Orchestra Testbench).
- Single file: `vendor/bin/pest tests/Unit/QrCodeServiceTest.php`; single test: `vendor/bin/pest --filter="can generate a PNG QR code"`.
- `composer analyse` (`vendor/bin/phpstan analyse`) — level 9, paths `src/` + `config/` only.
- `composer format` (fix) / `composer test-style` / `vendor/bin/pint --test` (CI check) — preset `laravel` plus `declare_strict_types`, `no_unused_imports`, `ordered_imports[alpha]`.
- Frontend: `npm run build` (Vite → `resources/dist/filament-qr-code.js` ES module + `resources/dist/chunks/` lazy decoder) after any `resources/js|css` change; commit both. `html5-qrcode` is bundled locally (no CDN) and loads once via dynamic `import()` on first camera use; initial bundle is ~20K.
- CI matrix: PHP 8.2–8.5 (`composer update`, special `--ignore-platform-req=php+` flag on 8.5); PHPStan + Pint run on 8.5.

## Architecture

- Entry: `src/FilamentQrCodeServiceProvider.php` (Spatie PackageTools; config file `qr-code`, views `filament-qr-code`; registers `QrCodeService` binding + `FilamentAsset` JS/CSS from `resources/dist/` via once-guarded `registerAssetsOnce()`, plus signed `filament-qr-code.image` route for lazy table previews). `src/FilamentQrCodePlugin.php` is intentionally thin (calls the same once-guard; install step optional BC).
- Backend generation: `src/Services/QrCodeService.php` over `bacon/bacon-qr-code` (SVG/PNG, caption/text overlay, `download()` response; L1 in-process + L2 Laravel-cache renders). Facade: `src/Facades/FilamentQrCode.php`.
- UI split by Filament area: `src/Forms/Components/` (`QrScanner`, `QrHardwareScannerListener`, `QrScanSequence`, `QrCollector`, `QrCodeDisplay`), `src/Tables/{Columns,QrColumn|Actions/QrCollectAction,DownloadQrAction}`, `src/Infolists/Components/QrEntry`. Shared behavior in `src/Concerns/` (`HasCameraScanning`, `HasQrRendering`, `HasHardwareScanner`, `HasSequentialScan`, `HasFeedback`); symbologies in `src/Enums/BarcodeFormat.php`.
- Frontend: `resources/js/index.js` registers Alpine `qrScanner|qrScanSequence|qrCollector|qrHardwareScannerListener` + `createHardwareScannerHandler`/`qrFeedback` on `window.FilamentQrCode`; per-mode files `qr-scanner|qr-sequence|qr-collector|qr-hardware-scanner|qr-camera-core|audio-feedback.js`. Views in `resources/views/`, config defaults in `config/qr-code.php` (burst 50ms, feedback beep 880Hz/80ms, camera fps 25/qrbox 250 responsive max).
- Tests: `tests/Pest.php` binds everything to `tests/TestCase.php` (registers all Filament + Livewire + Blade providers, sqlite `testing` DB). Layout: `tests/Unit/` (service, hardware scanner buffer), `tests/Feature/` (per-component + `LivewireFormE2ETest`, `LivewireTableE2ETest`).

## Gotchas

- PNG/text-overlay tests need GD + system fonts — CI installs `fonts-dejavu-core` and enables `gd, exif, imagick`. `QrCodeService::font('missing.ttf')` must gracefully fall back to GD bitmap fonts (covered by test); don't make missing fonts throw.
- Committed `resources/dist/` is what Filament serves — rebuilding with `npm run build` is required, don't hand-edit dist output. Filament v5 serves `Js`/`Css` asset objects from the host app's `public/` (never from package `dist/`), so host apps must run `php artisan filament:assets` after every build or they smoke-test stale code.
- Spatie PackageTools loads translations from `resources/lang/`, not `lang/`.
- PHPStan analyzes only `src` + `config`; keep new PHP files under those paths typed to level 9 and add `declare(strict_types=1)` (Pint enforces it).
- Every `$wire.set` triggers a Livewire morph that destroys `<video>` elements and reverts Alpine-held results: any Blade hosting camera/decoder DOM (viewfinders, scan checklists, collector lists) must be `wire:ignore`d, and live toggles affecting those islands must sync via window events, not re-renders.
- Preferred vocabulary is in `CONTEXT.md` (e.g. "Hardware Scanner / Hardware Scanner Interceptor / Station Listener", "Scan Sequence Container", "Batch Collector"); use those names for new APIs/docs. UI strings go under `filament-qr-code::ui.*` only.

## Review rules (judgement calls; CI handles the mechanical)

- One visible scan rectangle: the decoder library's own overlay (`#qr-shaded-region`) stays hidden in CSS; the custom reticle is sized by `syncReticleToQrbox()` against the shared viewfinder scope.
- One scan funnel per surface: a single `$wire` method per scan source. Never register both `$wire.handleCollectorScan` and a `qr-collector-item-added` window listener for the same flow — scans double-count. Field burst handlers stand down while a page-global `QrHardwareScannerListener` is mounted (`HasHardwareScanner::suppressWhenGlobalListenerActive`, default true); pass `->suppressWhenGlobalListener(false)` to force the field listener.
- `scanFormat()`, `onScan()`, `onStepScanned()` are programmatic-only: they never run on live camera or hardware scans. Live normalization belongs in `QrScanner::normalizeUsing()` / `QrScanSequence::normalizeStepUsing()` (or Filament's `afterStateUpdated()`); docs must say so, never imply otherwise.
- `QrScanSequence` binds through `statePathPrefix()`, which must match the schema's `statePath()`. Prefer `->statePath('sequence')` + `mergeSequenceState($this->form->getState())` / `isSequenceComplete()` over hand-rolled `array_merge($this->data, ...)`; `getSequenceState()` normalizes on read.
- Filter `formats()` on every camera component; `getEffectiveFps()` auto-degrades unrestricted 25fps to 12 unless `fps()` was explicitly called. `qrbox` is a responsive maximum, never a fixed square.
