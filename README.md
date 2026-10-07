# Filament QR Code

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mmuqiitf/filament-qr-code/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mmuqiitf/filament-qr-code/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![Total Downloads](https://img.shields.io/packagist/dt/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)

A powerful, modern QR code package designed exclusively for **Filament v5** and **Laravel 11 / 12**.

Features:

- 📷 **Interactive Camera Scanner**: Real-time camera stream, rear-camera prioritization with remembered choice, responsive decode box synced to the on-screen reticle, and image upload fallback with zero CDN latency.
- 🔗 **Sequential Scanning**: One shared camera feed walks through a multi-field checklist (`QrScanSequence`), with editable or locked steps.
- 🔫 **Hardware Scanner Support**: Native burst keystroke detection (<50ms) that absorbs trailing `Enter` keys to prevent premature form submissions.
- 📦 **Batch Collector (Repeaters & Lists)**: Continuous scanning mode with duplicate protection and sound/haptic confirmation for rapid inventory logging.
- 🎨 **Full QR Generator Suite**: Generate SVG & PNG QR codes with captions/text overlays, logo embedding, and schema components for Forms, Tables, Infolists, and Actions.
- 🔊 **Sensory Confirmation**: Instant zero-latency synthesized Web Audio tone and mobile haptic feedback, pitch and duration tunable per component.

---

- [Filament QR Code](#filament-qr-code)
  - [Requirements](#requirements)
  - [Installation](#installation)
    - [Symbologies](#symbologies)
  - [Usage](#usage)
    - [1. Individual QR Scanner Field](#1-individual-qr-scanner-field)
    - [2. Hands-Free Station Mode (`QrHardwareScannerListener`)](#2-hands-free-station-mode)
    - [3. Sequential Scanning — One Scanner, Many Fields](#3-sequential-scanning--one-scanner-many-fields)
      - [Correcting a scan (unedited vs edited values)](#correcting-a-scan-unedited-vs-edited-values)
      - [Caveat: `getState()` misses container writes](#caveat-getstate-misses-container-writes)
    - [4. Focus Handoff Between Fields](#4-focus-handoff-between-fields)
    - [5. Batch Collector Scanning (Repeaters \& Tables)](#5-batch-collector-scanning-repeaters--tables)
      - [In Form Repeaters:](#in-form-repeaters)
      - [As a Table / Repeater Action:](#as-a-table--repeater-action)
    - [6. QR Code Generator Components](#6-qr-code-generator-components)
      - [In Form Schemas:](#in-form-schemas)
      - [In Table Columns:](#in-table-columns)
      - [In Infolists:](#in-infolists)
      - [As a Download Action:](#as-a-download-action)
      - [Programmatic Standalone Generation:](#programmatic-standalone-generation)
  - [Tutorial: Cashier POS with a Handheld Scanner](#tutorial-cashier-pos-with-a-handheld-scanner)
    - [Step 1 — mount the hardware scanner listener](#step-1--mount-the-hardware-scanner-listener)
    - [Step 2 — funnel every scan into one method](#step-2--funnel-every-scan-into-one-method)
  - [Configuration](#configuration)
    - [`hardware_scanner`](#hardware_scanner)
    - [`feedback`](#feedback)
    - [`camera`](#camera)
    - [`generator`](#generator)
  - [Browser Events](#browser-events)
  - [Translations](#translations)
  - [Testing](#testing)
  - [Static Analysis](#static-analysis)
  - [Changelog](#changelog)
  - [Security Vulnerabilities](#security-vulnerabilities)
  - [Credits](#credits)
  - [License](#license)

---

## Requirements

- PHP `^8.2`, Laravel 11/12/13, Filament v5.
- PNG generation needs `gd` or `imagick` plus system fonts (`fonts-dejavu-core` on Debian).
- Camera scanning needs a secure context (`https` or `localhost`).
- The camera bundle (`html5-qrcode`) is compiled into `resources/dist/` — no CDN. After changing anything under `resources/js` or `resources/css`, rebuild with `npm run build` (CI fails when committed `dist/` is stale).

---

## Installation

You can install the package via composer:

```bash
composer require mmuqiitf/filament-qr-code
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag="filament-qr-code-config"
```

Register the plugin in your Filament Panel Provider (optional — JS/CSS
auto-register globally via the service provider, so existing installs that
already call `->plugin()` keep working with no duplicate tags):

```php
use Mmuqiitf\FilamentQrCode\FilamentQrCodePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentQrCodePlugin::make());
}
```

The camera decoder (`html5-qrcode`, ~375K) is split into a lazy chunk:
the global bundle is ~20K and the decoder downloads once, on first camera
use. Pages that only render QR images never fetch it.

### Symbologies

Pass `BarcodeFormat` cases (or raw strings) via `->formats([...])` on every camera component. Supported: `QrCode`, `Aztec`, `DataMatrix`, `Maxicode`, `Codabar`, `Code39`, `Code93`, `Code128`, `Itf`, `Ean13`, `Ean8`, `UpcA`, `UpcE`. Filtering formats speeds up decoding and cuts false positives — always set it when you know what you scan. One-dimensional codes automatically get a wide decode band instead of a square.

---

## Usage

### 1. Individual QR Scanner Field

Add a QR scanner field to your form schema with camera modal and hardware scanner integration:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;

QrScanner::make('sku')
    ->label('Product SKU / Barcode')
    ->formats([
        BarcodeFormat::QrCode,
        BarcodeFormat::Code128,
        BarcodeFormat::Code39,
        BarcodeFormat::Ean13,
    ])
    ->sound(true)
    ->vibrate(true)
    ->hardwareScanner(enabled: true, burstThresholdMs: 50)
    ->afterStateUpdated(function ($component, ?string $state): void {
        // Live-scan normalization belongs here: scanFormat() below never
        // runs on live camera/hardware scans (server-side hook only).
        $normalized = filled($state) ? strtoupper(trim($state)) : $state;

        if ($normalized !== $state) {
            $component->state($normalized);
        }
    });
```

Option reference:

- `formats()` — symbologies the decoder attempts. Omit only when truly unknown. With no filter the effective `fps` auto-degrades 25 → 12 against main-thread lag (explicit `fps()` always wins).
- `scanFormat()` / `onScan()` — programmatic-only hooks (used with `formatScannedValue()` / `triggerOnScan()` in custom flows). They do **not** run on live camera or hardware scans; use `normalizeUsing()` for live values:
```php
QrScanner::make('sku')
    ->normalizeUsing(fn ($rawValue) => strtoupper(trim((string) $rawValue)));
```
- `hardwareScanner(terminators: [...], minBarcodeLength: 2)` — burst tuning. Buffers are sanitized (STX/ETX/CR/LF stripped, mirroring `HasHardwareScanner::sanitizeScannedValue()`), IME compositions never count, and terminator-less guns flush after `scanTimeoutMs` (default 150).
- `scanRules(['min:3'])` — submit-time validation for scanned values. `rejectWhen(fn ($state) => ..., 'message')` clears bad live scans immediately and dispatches a `qr-scan-rejected` window event (with `{ message }`) so the app can notify.
- Field burst handlers stand down while a page-global `QrHardwareScannerListener` is mounted (`->suppressWhenGlobalListener(false)` forces the field listener to stay active).
- `nextField('other')` — focuses that field after a scan (field handoff, see §4).
- `fps()` / `qrbox()` — defaults 25 / 250. `qrbox` is a _maximum_: the actual decode box scales to the viewfinder and the green reticle follows it.
- `preferRearCamera()` — rear heuristic, but the operator's last camera choice (stored in `localStorage`) always wins.
- `allowUpload(false)` — hides the image-file fallback.
- `beepFrequency()` / `beepDuration()` / `vibrateDuration()` — feedback tuning.
- `hardwareScanner(terminators: [...], minBarcodeLength: 2)` — burst tuning.

The modal lists every detected camera (switching restarts the feed and is remembered) and closes automatically after a successful camera scan. Upload scans reuse the same decoder instance.

> Hardware scanner note: `QrScanner`'s burst listener is field-scoped on purpose. Mount `QrHardwareScannerListener` on the page for global capture, otherwise both listeners will double-handle the same burst.

### 2. Hands-Free Station Mode (`QrHardwareScannerListener`)

For manufacturing stations and warehouse counters where operators shoot barcodes without touching the mouse:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

QrHardwareScannerListener::make([
    'step',
    'employee',
    'document',
    'equipment',
])
    ->autoFocusNext(true)
    ->sound(true);
```

Add `QrHardwareScannerListener` anywhere in your schema. It intercepts hardware scanner bursts across the entire page, populates the active or first empty field (covering `text`, `search`, `number` inputs and textareas), syncs Livewire state, and auto-advances focus.

The interceptor buffers keystrokes and treats gaps under `burstThresholdMs` (default 50) as a scanner burst. On a terminator key with a long-enough buffer, it `preventDefault()`s (no accidental submit), beeps, and routes the value. Tune `terminators` and `minBarcodeLength` to your gun's suffix.

### 3. Sequential Scanning — One Scanner, Many Fields

Sequential scanning means **one scanner driving multiple fields**: a single shared camera feed (or one handheld gun) walks through a checklist. That is `QrScanSequence`:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

QrScanSequence::make([
    'step' => 'Operation Step',
    'employee' => 'Employee ID',
    'document' => 'Document Number',
    'equipment' => 'Equipment Code',
])
    ->fps(25)
    ->qrbox(250)
    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
    ->preferRearCamera()
    ->statePathPrefix('data') // Livewire form state prefix scans are written to
    ->sound(true)
    ->vibrate(true);
```

One feed, one checklist: steps auto-advance, clicking a step re-targets it, and the camera dropdown matches the single-field scanner. It renders stacked on mobile and split-screen on desktop. When the last step lands, the feed stops by itself so the button never gets stuck on "Stop".

`statePathPrefix()` (default `data`) is what keeps the container bound to your form — set it to whatever `statePath()` your schema uses. `scanFormat()` / `onStepScanned()` are programmatic-only hooks (they do not run on live scans); pair them with `formatScannedValue()` and `triggerOnStep()` in custom flows, and normalize live values with `normalizeStepUsing()` (applied on read/merge).

#### Reading sequence state (no more hand-rolled merges)

Give the container its own state and read it back through helpers:

```php
QrScanSequence::make(['batch_number', 'serial_number'])
    ->statePath('sequence')
    ->normalizeStepUsing(fn ($rawValue) => strtoupper(trim((string) $rawValue)));

// In your submit handler:
$sequence = /* resolve the QrScanSequence component */;
$state = $sequence->mergeSequenceState($this->form->getState());

if (! $sequence->isSequenceComplete($state)) {
    Notification::make()->title('Incomplete sequence')->warning()->send();
    return;
}
```

`getSequenceState()` prefers the component's own state and falls back to the legacy `statePathPrefix()` paths, so existing installs without `->statePath()` keep working. `getMissingSequenceKeys()` lists what's still empty.

#### Correcting a scan (unedited vs edited values)

Every step is a plain input: scans fill it, and operators can type or fix any value by hand — handy for unreadable labels. Every change syncs to the bound Livewire state and dispatches `qr-sequence-edited`. Pass `->editable(false)` to render locked read-only values instead. To flip modes live without a re-render wiping the running feed, dispatch `qr-sequence-editable` with `{ enabled }` on `window`; the component listens for it (its DOM is `wire:ignore`d so scans survive Livewire morphs).

#### Caveat: `getState()` and container writes

The container writes via `$wire.set`. When it has its own `->statePath()`, every scan syncs both the legacy prefix paths and the component state, so prefer `mergeSequenceState($this->form->getState())` over raw `array_merge` and validate with `isSequenceComplete()`:

```php
$state = array_merge($this->data ?? [], $this->form->getState());

if (blank($state['batch_number'] ?? null)) {
    Notification::make()->title('Incomplete sequence')->warning()->send();
    return;
}
```

### 4. Focus Handoff Between Fields

Move focus to the next field after each scan (a two-field handoff — distinct from sequential scanning above):

```php
QrScanner::make('batch_number')
    ->nextField('serial_number'),

QrScanner::make('serial_number')
    ->nextField('location_code'),

QrScanner::make('location_code'),
```

### 5. Batch Collector Scanning (Repeaters & Tables)

Continuous camera multi-scan for inventory and repeater flows (not the register — cashiers use a gun, see the tutorial below).

#### In Form Repeaters:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('scanned_items')
    ->allowDuplicates(false)
    ->delayBetweenScans(1200)
    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
    ->preferRearCamera();
```

#### As a Table / Repeater Action:

```php
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

$table->headerActions([
    QrCollectAction::make()
        ->allowDuplicates(false)
        ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
        ->onScan(fn ($code) => logger()->info("Collected {$code}")),
]);
```

Each scan dispatches a `qr-collector-item-added` window event (with `{ code }`). Forward it into server-side handling — e.g. `x-on:qr-collector-item-added.window` calling your Livewire method, which can then invoke `$action->handleScan($code)` to run the `onScan` callback. On the host, `$wire.handleCollectorScan($code)` is also honored when defined — but pick one channel, not both.

Every `handleScan()` call (and every programmatic `triggerOnScan()`) also fires a `QrCodeScanned` event (`code`, `source`, `field`, `context`). Listen for it yourself, or flip `audit.enabled` to record scans through your log stack (`audit.channel`, default stack). Live camera/hardware scans stay client-side — they enter the audit trail once they reach the server.

Custom terminators and minimum lengths are configurable via `->hardwareScanner(terminators: [...], minBarcodeLength: 3)` and feedback pitch/duration via `->beepFrequency(660)`, `->beepDuration(120)`, `->vibrateDuration(200)`. Server-side, `->distinctItems()` rejects duplicate codes on submit to match `->allowDuplicates(false)` in the browser.

### 6. QR Code Generator Components

#### In Form Schemas:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;

QrCodeDisplay::make('qr')
    ->data(fn ($record) => $record?->uuid)
    ->size(200)
    ->color('#1e293b')
    ->caption('Scan to verify')
    ->downloadable();
```

#### In Table Columns:

```php
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

QrColumn::make('sku')
    ->thumbnailSize(48)
    ->modalSize(300)
    ->previewable()
    ->downloadable();
```

Thumbnails and modal previews share a capped in-process render cache plus a persistent Laravel-cache L2 (`generator.cache_ttl`, default 86400s; `QrCodeService::persistentCache(false)` / `::cacheStore('redis')` to tune). The modal image loads lazily through a signed `filament-qr-code.image` route, so a 25-row table encodes 25 thumbnails instead of 50 mixed-size images — `->lazyModal(false)` restores eager data-URIs.

#### In Infolists:

```php
use Mmuqiitf\FilamentQrCode\Infolists\Components\QrEntry;

QrEntry::make('verification_code')
    ->size(200)
    ->caption('Official Verification QR');
```

#### As a Download Action:

```php
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;

$table->actions([
    DownloadQrAction::make()
        ->qrData(fn ($record) => $record->verification_url)
        ->qrFileName(fn ($record) => "qr-{$record->id}")
        ->qrFormat(QrFormat::Png)
        ->qrImageSize(400)
        ->qrMargin(2),
]);
```

#### Programmatic Standalone Generation:

```php
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

// Generate data URI
$dataUri = FilamentQrCode::make()
    ->format(QrFormat::Svg)
    ->size(300)
    ->margin(2)
    ->color('#0f172a')
    ->backgroundColor('#ffffff')
    ->errorCorrection('M') // L, M, Q, H
    ->generate('https://example.com')
    ->toDataUri();

// Generate PNG with Text Overlay & Download
return FilamentQrCode::make()
    ->withText('BATCH #1024', 16, '#000000')
    ->generate('BATCH-1024')
    ->download('batch-1024'); // StreamedResponse download; stream() inlines
```

Rules that bite:

- `withText()` and `logo()` need raster output, so the service always returns PNG when either is set — regardless of whether `format()` was called before or after. Request PNG explicitly when overlaying.
- Invalid hex colors throw `InvalidArgumentException` instead of silently rendering black.
- `logo($path, $size)` forces error-correction level H for scannability.
- Identical payloads share the render cache, so tables with repeated values encode once.

#### Common payloads without hand-escaping

```php
use Mmuqiitf\FilamentQrCode\Support\QrPayload;

QrPayload::wifi('Shop Floor', 'secret-1');                    // WPA (nopass + hidden supported)
QrPayload::vcard(['firstName' => 'Siti', 'phone' => '...']);   // escaped vCard 3.0
QrPayload::mailto('ops@example.com', 'Stock alert', '...');   // mailto with subject/body
QrPayload::sms('+621234567', 'Arrived');                     // SMSTO
QrPayload::geo(-6.2, 106.8, 'Warehouse 7');                   // geo with query
```

---

## Tutorial: Cashier POS with a Handheld Scanner

Cashiers scan with a physical gun, so there is **no camera UI** — just an input box and the invisible hardware scanner interceptor catching bursts anywhere on the page.

### Step 1 — mount the hardware scanner listener

A Filament page needs a schema to host the component:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

public ?array $data = [];

public function form(Schema $schema): Schema
{
    return $schema->statePath('data')->components([
        QrHardwareScannerListener::make(['scanInput'])
            ->autoFocusNext(false) // stay on the SKU box for rapid scans
            ->sound(true)
            ->hardwareScanner(terminators: ['Enter', 'Tab'], minBarcodeLength: 2),
    ]);
}
```

Render `{{ $this->form }}` — it outputs nothing visible.

### Step 2 — funnel every scan into one method

Gun bursts and typed input must converge, or quantities double-count:

```blade
<div x-on:qr-hardware-scanned.window="$wire.scanProduct($event.detail.value)">
```

```php
public function scanProduct(string $code): void
{
    $product = Product::where('sku', trim($code))
        ->orWhere('barcode', trim($code))->first();

    if (! $product) {
        Notification::make()->title('Product Not Found')->danger()->send();
        return;
    }

    // bump quantity if already in cart, else push a new line
}
```

Keep the manual input's Enter handler: scanner bursts swallow their terminator (`preventFormSubmit`), so Enter only fires for typed input — no double-adds.

---

## Configuration

Publish the config to tune defaults (`config/qr-code.php`):

```bash
php artisan vendor:publish --tag="filament-qr-code-config"
```

Per-component options override these defaults:

```php
QrScanner::make('sku')
    ->fps(25)
    ->qrbox(250) // responsive max; the decode box scales to the viewfinder
    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
    ->preferRearCamera()
    ->hardwareScanner(terminators: ['Enter', 'Tab'], minBarcodeLength: 2)
    ->beepFrequency(880)
    ->beepDuration(80)
    ->vibrateDuration(100);
```

### `hardware_scanner`

| Key                   | Default            | Meaning                                                              |
| --------------------- | ------------------ | -------------------------------------------------------------------- |
| `enabled`             | `true`             | master switch (components also gate individually)                    |
| `burst_threshold_ms`  | `50`               | max gap between keystrokes counted as one burst                      |
| `min_barcode_length`  | `2`                | shorter bursts are treated as typing                                 |
| `scan_timeout_ms`     | `150`              | flush window for terminator-less guns (0 disables)                   |
| `prevent_form_submit` | `true`             | swallow the terminator key during bursts                             |
| `default_terminators` | `['Enter', 'Tab']` | gun suffix keys ending a scan                                        |

### `feedback`

| Key                   | Default  |
| --------------------- | -------- |
| `sound`               | `true`   |
| `beep_frequency`      | `880` Hz |
| `beep_duration_ms`    | `80`     |
| `vibrate`             | `true`   |
| `vibrate_duration_ms` | `100`    |

### `camera`

| Key                  | Default | Meaning                                                                                                  |
| -------------------- | ------- | -------------------------------------------------------------------------------------------------------- |
| `fps`                | `25`    | decode attempts per second; auto-degrades to 12 when unrestricted and not explicitly set                  |
| `qrbox`              | `250`   | _maximum_ decode-box edge; the real box scales to the viewfinder (wide band for 1D)                      |
| `prefer_rear_camera` | `true`  | rear heuristic; the remembered `localStorage` choice wins                                                |

### `generator`

`size` (300), `margin` (2), `format` (`svg`), `foreground_color`, `background_color`, `error_correction` (`M`), `cache_ttl` (86400; `null` = forever, `false` = L2 off), `cache_store` (`null` = default store).

---

## Browser Events

| Event                     | Detail                        | Fired when                |
| ------------------------- | ----------------------------- | ------------------------- |
| `qr-scanned`              | `{ value, field, nextField }` | any `QrScanner` scan      |
| `qr-scan-rejected`        | `{ message }`                 | a `rejectWhen()` predicate matched |
| `qr-hardware-scanned`        | `{ value, field }`            | page-global hardware burst   |
| `qr-sequence-step`        | `{ field, value, index }`     | each container step       |
| `qr-sequence-completed`   | `{ results }`                 | last container step       |
| `qr-sequence-edited`      | `{ field, value }`            | operator edits a step     |
| `qr-collector-item-added` | `{ code }`                    | each batch-collector scan |

---

## Translations

All UI strings live under the `filament-qr-code::ui` translation namespace (`resources/lang/en/ui.php`). Publish with:

```bash
php artisan vendor:publish --tag="filament-qr-code-translations"
```

and translate per locale.

---

## Troubleshooting

Run the built-in checks first:

```bash
php artisan qr-code:doctor
```

| Symptom | Likely cause | Fix |
| --- | --- | --- |
| Camera modal says no devices / access denied | Page served over plain `http` (not `localhost`) | Serve via `https` or test on `localhost`; browsers block cameras in insecure contexts. |
| Camera modal empty on first open | Permission not granted yet, labels unavailable | Grant permission, reopen; the remembered `localStorage` choice wins afterwards. |
| Stale scanner UI after updating the package | Committed `dist/` rebuilt but host serving old assets | `npm run build` in the package, then `php artisan filament:assets` in the host app. |
| PNG looks wrong / text overlay is blocky | Missing GD/Imagick or system fonts | Install `gd` or `imagick` plus `fonts-dejavu-core`; the service falls back to GD bitmap fonts otherwise. |
| Sequence submit misses scanned values | `statePathPrefix()` doesn't match the schema `statePath()` | Set both to the same prefix, or give the sequence `->statePath()` and read via `mergeSequenceState()`. A banner warns in the UI when they differ. |
| Same burst handled twice | Field `QrScanner` listener + page `QrHardwareScannerListener` both active | Keep the global listener; field handlers stand down automatically (or `->suppressWhenGlobalListener(false)`). |
| Typed text becomes a "scan" | `minBarcodeLength` too low for a keyboard-heavy form | Raise `minBarcodeLength` to 4–6 on that component. |
| Table page is slow with many QRs | Eager modal data-URIs per row | Keep `->lazyModal()` (default) and the persistent cache enabled; tune `generator.cache_ttl`. |

## Testing

```bash
composer test
```

Run a single file or test with `vendor/bin/pest tests/Unit/QrCodeServiceTest.php` or `vendor/bin/pest --filter="can generate a PNG QR code"`.

## Static Analysis

```bash
composer analyse
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mohamad Muqiit Faturrahman](https://github.com/mmuqiitf)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
