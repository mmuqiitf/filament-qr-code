# Filament QR Code

<img src="docs/banner.png" alt="Filament QR &amp; Barcode banner" class="filament-hidden">

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mmuqiitf/filament-qr-code/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mmuqiitf/filament-qr-code/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![Total Downloads](https://img.shields.io/packagist/dt/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)

A powerful, modern QR code package designed exclusively for **Filament v5** and **Laravel 12 / 13**.

Features:

- 📷 **Interactive Camera Scanner**: Real-time camera stream, rear-camera prioritization with remembered choice, responsive decode box synced to the on-screen reticle, and image upload fallback with zero CDN latency.
- 🔗 **Sequential Scanning**: One shared camera feed walks through a multi-field checklist (`QrScanSequence`), with editable or locked steps.
- 🔫 **Hardware Scanner Support**: Native burst keystroke detection (<50ms) that absorbs trailing `Enter` keys to prevent premature form submissions.
- 📦 **Batch Collector (Repeaters & Lists)**: Continuous scanning mode with duplicate protection and sound/haptic confirmation for rapid inventory logging.
- 🎨 **Full QR Generator Suite**: Generate SVG & PNG QR codes with captions/text overlays, logo embedding, and schema components for Forms, Tables, Infolists, and Actions.
- 🔊 **Sensory Confirmation**: Instant zero-latency synthesized Web Audio tone and mobile haptic feedback, pitch and duration tunable per component.

---

- [Filament QR Code](#filament-qr-code)
  - [Try it](#try-it)
  - [Requirements](#requirements)
  - [How it works](#how-it-works)
  - [Getting started in 5 minutes](#getting-started-in-5-minutes)
  - [Usage](#usage)
    - [Which component do I need?](#which-component-do-i-need)
    - [Next steps](#next-steps)
    - [Documentation](#documentation)
    - [For AI agents and LLMs](#for-ai-agents-and-llms)
  - [Troubleshooting](#troubleshooting)
  - [Reporting issues](#reporting-issues)
  - [Contributing](#contributing)
  - [Changelog](#changelog)
  - [Security Vulnerabilities](#security-vulnerabilities)
  - [Credits](#credits)
  - [License](#license)

---

## Try it

The [filament-qr-code-example](https://github.com/mmuqiitf/filament-qr-code-example) app demos every component (scanner showcase, cashier POS, work-order sequence, QR customizer, products resource):

```bash
git clone https://github.com/mmuqiitf/filament-qr-code-example && cd filament-qr-code-example
composer setup            # composer + npm install, .env, key, SQLite migrations, asset build
php artisan db:seed       # admin@example.com / password
composer dev              # http://localhost:8081/admin
```

---

## Screenshots

From the [example app](https://github.com/mmuqiitf/filament-qr-code-example) (seeded demo data).

| | | |
|---|---|---|
| ![Products table with QR column](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/products-table-qr-column.png)<br>Products table with QR column | ![QR preview modal](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/products-qr-preview-modal.png)<br>QR preview modal | ![Bulk ZIP export](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/products-bulk-zip-export.png)<br>Bulk ZIP export |
| ![QR customizer](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/qr-customizer.png)<br>QR customizer | ![Print sheet](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/qr-labels-print-sheet.png)<br>Print sheet | ![Scanner modal](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/scanner-modal-camera.png)<br>Scanner modal |
| ![Server check accepted](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/server-checks-accepted.png)<br>Server check accepted | ![Server check rejected](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/server-checks-rejected.png)<br>Server check rejected | ![POS checkout](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/pos-checkout.png)<br>POS checkout |
| ![Station listener](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/cashier-station-listener.png)<br>Station listener | ![Goods receiving](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/goods-receiving-received.png)<br>Goods receiving | ![Work order sequence](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/work-order-sequence.png)<br>Work order sequence |
| ![Manufacturing paused](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/manufacturing-paused.png)<br>Manufacturing paused | ![Shelf audit collector](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/shelf-audit-collector.png)<br>Shelf audit collector | ![Shelf audit recorded](https://raw.githubusercontent.com/mmuqiitf/filament-qr-code/main/docs/images/shelf-audit-recorded.png)<br>Shelf audit recorded |

## Requirements

- PHP `^8.2`, Laravel 12/13, Filament v5.
- PNG generation needs `gd` or `imagick` plus system fonts (`fonts-dejavu-core` on Debian).
- Camera scanning needs a secure context (`https` or `localhost`).
- The camera bundle (`html5-qrcode`) is compiled into `resources/dist/` — no CDN. After changing anything under `resources/js` or `resources/css`, rebuild with `npm run build` (CI fails when committed `dist/` is stale).

---

## How it works

- **Camera scanning** runs fully in the browser: a modal viewfinder streams the camera through the bundled decoder. The decoder chunk lazy-loads on first camera use; pages that only render QR images never fetch it.
- **Hardware scanners** emulate keyboards: they burst keystrokes in a few dozen milliseconds and end with a terminator key (`Enter`/`Tab`). The interceptor buffers bursts, swallows the terminator so forms don't submit, and routes the value to the active field.
- **Sequences and collectors** write through `$wire.set` into your Livewire form state, so scanned values behave like typed input (validation and reactivity included). Camera DOM lives under `wire:ignore` so Livewire morphs never kill a running feed.
- **Generation** is server-side: renders are cached in-process plus in the Laravel cache, so repeated values encode once. Table modal previews load lazily through a signed image route instead of embedding a data-URI per row.

---

## Getting started in 5 minutes

1. **Install** the package:

   ```bash
   composer require mmuqiitf/filament-qr-code
   ```

2. **Register the plugin** in your panel provider. This step is optional, because the service provider registers the JS and CSS for every panel, but it keeps the setup explicit:

   ```php
   use Filament\Panel;
   use Mmuqiitf\FilamentQrCode\FilamentQrCodePlugin;

   public function panel(Panel $panel): Panel
   {
       return $panel
           // ...
           ->plugin(FilamentQrCodePlugin::make());
   }
   ```

3. **Add the package views to your theme.** Buttons, overlays and layout use Tailwind classes in the package views, so your panel theme must scan them. Create a theme if you don't have one (`php artisan make:filament-theme`), add the `@source` line, then run `npm run build`:

   ```css
   /* resources/css/filament/admin/theme.css */
   @source '../../../../vendor/mmuqiitf/filament-qr-code/resources/views/**/*';
   ```

4. **Publish the assets** (run it again after every package update):

   ```bash
   php artisan filament:assets
   ```

5. **Add your first field** to a resource form:

   ```php
   use Filament\Schemas\Schema;
   use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
   use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

   public static function configure(Schema $schema): Schema
   {
       return $schema->components([
           QrScanner::make('sku')
               ->label('SKU')
               ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128]),
       ]);
   }
   ```

**Expected result:** the form shows a text input with a camera button. The button opens a modal viewfinder. It needs `https` or `localhost` and asks for camera permission. The modal also has an image-upload fallback. A decoded code fills the input and closes the modal. A USB or Bluetooth gun typing into the focused input fills it too, and its `Enter` doesn't submit the form. If the buttons look unstyled, step 3 is missing. Run `php artisan qr-code:doctor` to check your setup.

The full walkthrough, with config publishing, symbologies and a first server check, is in [Getting started](docs/getting-started.md).

---

## Usage

### Which component do I need?

| Job                                                 | Use                                                                                                                                                                                                                      | Why not the others                                                                 |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------- |
| One input scanned by camera, upload, typing, or gun | [`QrScanner`](docs/scanning.md#qr-scanner-field-qrscanner)                                                                                                                                                            | Sequence/collector add moving parts a single field doesn't need.                   |
| Cashier/POS gun, no camera UI                       | [`QrHardwareScannerListener`](docs/scanning.md#station-listener-qrhardwarescannerlistener) + [one funnel method](docs/scanning.md#step-2--funnel-every-scan-into-one-method)                                    | `QrScanner`'s field listener would double-handle the same burst.                   |
| One camera walking many fields in order             | [`QrScanSequence`](docs/scanning.md#scan-sequence-container-qrscansequence)                                                                                                                                      | Chained `nextField()` hops between separate cameras; the sequence shares one feed. |
| Two fields, hand focus from one to the next         | [`QrScanner::nextField()`](docs/scanning.md#sequential-field-scanning-nextfield)                                                                                                                                              | A sequence is overkill without a shared checklist.                                 |
| Many scans into one list (stocktake, receiving)     | [`QrCollector` / `QrCollectAction`](docs/collecting.md#batch-collector-scanning-qrcollector-qrcollectaction)                                                                                                                     | Sequences map one scan to one field; collectors append.                            |
| Show a QR (form, table, infolist, download)         | [`QrCodeDisplay`](docs/generating.md#in-form-schemas) / [`QrColumn`](docs/generating.md#in-table-columns) / [`QrEntry`](docs/generating.md#in-infolists) / [`DownloadQrAction`](docs/generating.md#as-a-download-action) | Scanner components capture; these only render.                                     |
| Many QRs out at once (labels, handover)             | [`DownloadQrBulkAction`](docs/generating.md#as-a-bulk-zip-export) + print sheet                                                                                                                                          | Single downloads don't scale past a handful of rows.                               |

### Next steps

- **Scanning**: [QR Scanner Field](docs/scanning.md#qr-scanner-field-qrscanner), [Station Listener](docs/scanning.md#station-listener-qrhardwarescannerlistener) for hands-free guns, [Scan Sequence Container](docs/scanning.md#scan-sequence-container-qrscansequence), [server-side checks](docs/scanning.md#server-side-checks) and the [cashier POS tutorial](docs/scanning.md#tutorial-cashier-pos-with-a-handheld-scanner).
- **Batch Collector Scanning**: [`QrCollector` / `QrCollectAction`](docs/collecting.md).
- **Generating**: [display components, downloads, ZIP export and payload builders](docs/generating.md).
- **Tuning**: config defaults are in [Reference](docs/reference.md#configuration), and component setters always win over them. Live-scan normalization and audit logging are in [Customizing](docs/customizing.md#normalize-live-scans).
- **Every public method**: the generated [API map](docs/api.md).

### Documentation

| Page | What it covers |
| --- | --- |
| [Getting started](docs/getting-started.md) | install to first working scan, step by step |
| [Scanning](docs/scanning.md) | QR Scanner Field, Station Listener, Scan Sequence Container, focus handoff, server checks, continuous mode, runtime control, POS tutorial |
| [Batch collecting](docs/collecting.md) | continuous inventory scanning in repeaters and tables |
| [Generating](docs/generating.md) | display components, download actions, ZIP export, facade, payloads |
| [Customizing](docs/customizing.md) | custom lookups, normalization, check stages, hooks, auditing, macros, styling, render hooks, views |
| [Extension points](docs/extending.md) | every seam in one table: PHP closures, contracts, macros, render hooks, CSS variables, JS decoder, router, events |
| [Events](docs/events.md) | every browser event fired or listened for, and every Laravel event with its properties |
| [Reference](docs/reference.md) | every config key, translations, the `@internal` policy |
| [API map](docs/api.md) | every public class, method, enum and config key (generated) |
| [FAQ](docs/faq.md) | iOS torch, secure context, offline use, guns, multiple panels |
| [Upgrade guide](UPGRADE.md) | breaking changes between versions |
| Recipes | [POS checkout](docs/recipes/pos.md) · [Goods receiving](docs/recipes/warehouse.md) · [Work-order station](docs/recipes/manufacturing.md) · [Label generator page](docs/recipes/generator-page.md) · [Custom inputs](docs/recipes/custom-input.md) · [Styling](docs/recipes/styling.md) · [ZXing decoder](docs/recipes/zxing-decoder.md). Each one runs in the [example app](https://github.com/mmuqiitf/filament-qr-code-example). |

### For AI agents and LLMs

- [AI guide](docs/ai.md): a component picker, invariants, common mistakes and full-import examples for coding agents.
- [`llms.txt`](llms.txt) is the index, and [`llms-full.txt`](llms-full.txt) has every doc in one file.
- [Laravel Boost](https://laravel.com/docs/boost) picks up the bundled guideline (`resources/boost/guidelines/core.blade.php`) and skill when you run `php artisan boost:install`.

---

## Troubleshooting

Run the built-in checks first:

```bash
php artisan qr-code:doctor
```

| Symptom                                      | Likely cause                                                              | Fix                                                                                                                                               |
| -------------------------------------------- | ------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Camera modal says no devices / access denied | Page served over plain `http` (not `localhost`)                           | Serve via `https` or test on `localhost`; browsers block cameras in insecure contexts.                                                            |
| Camera modal empty on first open             | Permission not granted yet, labels unavailable                            | Grant permission, reopen; the remembered `localStorage` choice wins afterwards.                                                                   |
| Unstyled buttons, layout differs from the panel | Panel theme doesn't `@source` the package views                    | Add `@source '../../../../vendor/mmuqiitf/filament-qr-code/resources/views/**/*';` to your theme CSS and rebuild ([Styling](docs/getting-started.md#3-add-the-package-views-to-your-theme)). |
| Stale scanner UI after updating the package  | Committed `dist/` rebuilt but host serving old assets                     | `npm run build` in the package, then `php artisan filament:assets` in the host app.                                                               |
| PNG looks wrong / text overlay is blocky     | Missing GD/Imagick or system fonts                                        | Install `gd` or `imagick` plus `fonts-dejavu-core`; the service falls back to GD bitmap fonts otherwise.                                          |
| Sequence submit misses scanned values        | The form reads the steps from a different place than the sequence writes them | Give the sequence `->statePath()` and read it via `mergeSequenceState()`; the flat `{form}.{step}` paths follow the schema `statePath()`. |
| Same burst handled twice                     | A custom key handler next to the package's wedge router                   | Register the handler with `FilamentQrCode.router.register()` so each burst has one owner ([Extension points](docs/extending.md#browser)).         |
| Typed text becomes a "scan"                  | `minBarcodeLength` too low for a keyboard-heavy form                      | Raise `minBarcodeLength` to 4–6 on that component.                                                                                                |
| Scan lands in the wrong input (e.g. search)  | Station Listener fell back to the first empty form input                  | `QrHardwareScannerListener::make([...])->fallbackToAnyInput(false)` (or `hardware_scanner.fallback_to_any_input => false`); listen for `qr-scan-unrouted`. |
| A slow or Bluetooth gun's short codes are missed | `min_fast_keys` (default 3) needs three fast gaps in a row               | Lower it on that component with `->minFastKeys(1)` (or `hardware_scanner.min_fast_keys`).                                                         |
| `nextField()` / listener misses a field      | Name does not match exactly                                               | Use the exact field name; nested paths like `items.0.sku` are supported, and `sku` never matches `parent_sku`.                                   |
| Table page is slow with many QRs             | Eager modal data-URIs per row                                             | Keep `->lazyModal()` (default) and the persistent cache enabled; tune `generator.cache_ttl`.                                                      |

## Security notes

Lazy modal previews are served by the signed `filamentQrCode.image` route. Signatures already stop tampering; if QR data or logo paths can come from users, turn on the opt-in hardening (all off by default): `generator.logo_base_paths` limits logos to listed directories, and `image_route.ttl` / `throttle` / `strict_colors` add expiring URLs, a rate limit and `#rrggbb`-only colours. See [Image route security](docs/generating.md#image-route-security).

## Reporting issues

1. Run `php artisan qr-code:doctor` and include its output.
2. Include your PHP / Laravel / Filament versions (`php -v`, `composer show laravel/framework filament/filament`), plus for camera issues the browser and whether the page is served over `https`/`localhost`, and for gun issues the scanner model and its suffix keys.
3. Describe expected vs actual, with a minimal schema snippet that reproduces it.

Security vulnerabilities are handled privately — see [Security Vulnerabilities](#security-vulnerabilities), not public issues.

## Contributing

PRs welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for the test suite, static analysis, code style, and frontend build workflow.

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently, and [UPGRADE.md](UPGRADE.md) when moving between minor versions.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mohamad Muqiit Faturrahman](https://github.com/mmuqiitf)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
