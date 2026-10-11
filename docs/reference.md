# Reference

> Back to [README](../README.md) · [Scanning](scanning.md) · [Batch collecting](collecting.md) · [Generating](generating.md)

Configuration, browser events, translations, customizing, and extending.

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

| Key                   | Default            | Meaning                                            |
| --------------------- | ------------------ | -------------------------------------------------- |
| `enabled`             | `true`             | master switch (components also gate individually)  |
| `burst_threshold_ms`  | `50`               | max gap between keystrokes counted as one burst    |
| `min_barcode_length`  | `2`                | shorter bursts are treated as typing               |
| `scan_timeout_ms`     | `150`              | flush window for terminator-less guns (0 disables) |
| `prevent_form_submit` | `true`             | swallow the terminator key during bursts           |
| `default_terminators` | `['Enter', 'Tab']` | gun suffix keys ending a scan                      |

These values are the defaults behind every scan surface (`QrScanner`, `QrScanSequence`, `QrCollector`, `QrHardwareScannerListener`, `QrCollectAction`): each component ships them to the browser as one payload (`getScanPayload()`), and any explicit setter (`->fps()`, `->sound()`, `->hardwareScanner()`, …) wins over config.

### `feedback`

| Key                   | Default  |
| --------------------- | -------- |
| `sound`               | `true`   |
| `beep_frequency`      | `880` Hz |
| `beep_duration_ms`    | `80`     |
| `vibrate`             | `true`   |
| `vibrate_duration_ms` | `100`    |

### `camera`

| Key                  | Default | Meaning                                                                                  |
| -------------------- | ------- | ---------------------------------------------------------------------------------------- |
| `fps`                | `25`    | decode attempts per second; auto-degrades to 12 when unrestricted and not explicitly set |
| `qrbox`              | `250`   | _maximum_ decode-box edge; the real box scales to the viewfinder (wide band for 1D)      |
| `prefer_rear_camera` | `true`  | rear heuristic; the remembered `localStorage` choice wins                                |

### `generator`

`size` (300), `margin` (2), `format` (`svg`), `foreground_color`, `background_color`, `error_correction` (`M`), `cache_ttl` (86400; `null` = forever, `false` = L2 off), `cache_store` (`null` = default store).

---

## Browser Events

| Event                     | Detail                        | Fired when                         |
| ------------------------- | ----------------------------- | ---------------------------------- |
| `qr-scanned`              | `{ value, field, nextField }` | any `QrScanner` scan               |
| `qr-scan-rejected`        | `{ message }`                 | a `rejectWhen()` predicate matched |
| `qr-hardware-scanned`     | `{ value, field }`            | page-global hardware burst         |
| `qr-sequence-step`        | `{ field, value, index }`     | each container step                |
| `qr-sequence-completed`   | `{ results }`                 | last container step                |
| `qr-sequence-edited`      | `{ field, value }`            | operator edits a step              |
| `qr-collector-item-added` | `{ code }`                    | each batch-collector scan          |

---

## Translations

All UI strings live under the `filament-qr-code::ui` translation namespace (`resources/lang/en/ui.php`, plus Indonesian in `resources/lang/id/ui.php`). Publish with:

```bash
php artisan vendor:publish --tag="filament-qr-code-translations"
```

and translate per locale.

---

## Customizing

- **Defaults**: publish `config/qr-code.php` (`php artisan vendor:publish --tag="filament-qr-code-config"`) and tune `hardware_scanner`, `feedback`, `camera`, `generator`, `audit`. Every per-component method (`->fps()`, `->beepFrequency()`, `->hardwareScanner(...)`, …) overrides its config default — see [Configuration](#configuration).
- **Language**: UI strings live under `filament-qr-code::ui`. Publish with `--tag="filament-qr-code-translations"` and translate per locale — see [Translations](#translations).
- **Print**: wrap QR images in `.filament-qr-label-sheet` / `.filament-qr-label` for a three-per-row shelf-label print sheet (camera UI auto-hides in print).
- **Generator look**: `size()`, `margin()`, `color()`, `backgroundColor()`, `errorCorrection()`, `caption()`, `withText()`, `logo()` — see [§6](generating.md#6-qr-code-generator-components). `withText()` / `logo()` force PNG output.

---

## Extending

### Normalize live scans

`scanFormat()` / `onScan()` / `onStepScanned()` never run on live camera or hardware scans — they are programmatic-only. Normalize live values instead:

```php
QrScanner::make('sku')
    ->normalizeUsing(fn ($rawValue) => strtoupper(trim((string) $rawValue)));

QrScanSequence::make([...])
    ->normalizeStepUsing(fn ($rawValue) => strtoupper(trim((string) $rawValue)));
```

Filament's `afterStateUpdated()` works too. Reject bad live scans with `->rejectWhen(fn ($state) => ..., 'message')`, which clears the value and dispatches `qr-scan-rejected`.

### Drive scans programmatically

Pair the programmatic-only hooks with their triggers in custom flows and tests:

```php
$scanner->formatScannedValue($raw); // runs scanFormat()
$scanner->triggerOnScan($value);    // runs onScan()
$sequence->formatScannedValue($raw);
$sequence->triggerOnStep($fieldKey, $value); // runs onStepScanned()
```

Every `handleScan()` / `triggerOnScan()` call also fires a `QrCodeScanned` event.

### Audit scans

Live scans stay client-side until they reach the server. Flip `audit.enabled` on to log server-observed scans through your log stack (`audit.channel`, default stack), or listen yourself:

```php
use Illuminate\Support\Facades\Event;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;

Event::listen(QrCodeScanned::class, function (QrCodeScanned $event): void {
    logger()->info("Scanned {$event->code} via {$event->source}");
});
```

The event carries `code`, `source`, `field`, and `context`.

### Common payload builders

`QrPayload::wifi()`, `::vcard()`, `::mailto()`, `::sms()`, `::geo()` build correctly escaped payloads — see [Common payloads](generating.md#common-payloads-without-hand-escaping).

### Validate on submit

`QrScanner::scanRules([...])` adds submit-time rules for the field; `QrCollector::distinctItems()` rejects duplicates server-side to match `->allowDuplicates(false)` in the browser.
