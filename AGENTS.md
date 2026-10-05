# AGENTS.md

Filament v5 + Laravel 11/12/13 QR package (`mmuqiitf/filament-qr-code`). PHP `^8.2`, local PHP `8.5`.

## Commands

- `composer test` / `vendor/bin/pest` — full suite (Pest 3 + Orchestra Testbench).
- Single file: `vendor/bin/pest tests/Unit/QrCodeServiceTest.php`; single test: `vendor/bin/pest --filter="can generate a PNG QR code"`.
- `composer analyse` (`vendor/bin/phpstan analyse`) — level 9, paths `src/` + `config/` only.
- `composer format` (fix) / `composer test-style` / `vendor/bin/pint --test` (CI check) — preset `laravel` plus `declare_strict_types`, `no_unused_imports`, `ordered_imports[alpha]`.
- Frontend: `npm run build` (Vite → `resources/dist/filament-qr-code.js`, IIFE) after any `resources/js|css` change; `npm run dev` for watch. `html5-qrcode` is bundled locally, no CDN.
- CI matrix: PHP 8.2–8.5 (`composer update`, special `--ignore-platform-req=php+` flag on 8.5); PHPStan + Pint run on 8.5.

## Architecture

- Entry: `src/FilamentQrCodeServiceProvider.php` (Spatie PackageTools; config file `qr-code`, views `filament-qr-code`; registers `QrCodeService` binding + `FilamentAsset` JS/CSS from `resources/dist/`). `src/FilamentQrCodePlugin.php` is a stub (`register`/`boot` are no-ops).
- Backend generation: `src/Services/QrCodeService.php` over `bacon/bacon-qr-code` (SVG/PNG, caption/text overlay, `download()` response). Facade: `src/Facades/FilamentQrCode.php`.
- UI split by Filament area: `src/Forms/Components/` (`QrScanner`, `QrWedgeListener`, `QrScanSequence`, `QrCollector`, `QrCodeDisplay`), `src/Tables/{Columns,QrColumn|Actions/QrCollectAction,DownloadQrAction}`, `src/Infolists/Components/QrEntry`. Shared behavior in `src/Concerns/` (`HasHardwareScanner`, `HasSequentialScan`, `HasFeedback`); symbologies in `src/Enums/BarcodeFormat.php`.
- Frontend: `resources/js/index.js` registers Alpine `qrScanner|qrScanSequence|qrCollector|qrWedgeListener` + `createWedgeHandler`/`qrFeedback` on `window.FilamentQrCode`; per-mode files `qr-scanner|qr-sequence|qr-collector|qr-wedge|audio-feedback.js`. Views in `resources/views/`, config defaults in `config/qr-code.php` (wedge burst 50ms, feedback beep 880Hz/80ms, camera fps 15/qrbox 250).
- Tests: `tests/Pest.php` binds everything to `tests/TestCase.php` (registers all Filament + Livewire + Blade providers, sqlite `testing` DB). Layout: `tests/Unit/` (service, wedge buffer), `tests/Feature/` (per-component + `LivewireFormE2ETest`, `LivewireTableE2ETest`).

## Gotchas

- PNG/text-overlay tests need GD + system fonts — CI installs `fonts-dejavu-core` and enables `gd, exif, imagick`. `QrCodeService::font('missing.ttf')` must gracefully fall back to GD bitmap fonts (covered by test); don't make missing fonts throw.
- Committed `resources/dist/` is what Filament serves — rebuilding with `npm run build` is required, don't hand-edit dist output.
- PHPStan analyzes only `src` + `config`; keep new PHP files under those paths typed to level 9 and add `declare(strict_types=1)` (Pint enforces it).
- Preferred vocabulary is in `CONTEXT.md` (e.g. "Hardware Wedge Scanner / Wedge Interceptor / Station Listener", "Scan Sequence Container", "Batch Collector"); use those names for new APIs/docs.
