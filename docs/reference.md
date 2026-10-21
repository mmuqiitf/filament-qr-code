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
    ->minFastKeys(3) // consecutive fast keystrokes before a terminator counts
    ->beepFrequency(880)
    ->beepDuration(80)
    ->vibrateDuration(100)
    // server-checked fields only (see scanning.md → Feedback)
    ->beep(success: true, error: true)
    ->vibrate(success: [100], error: [80, 60, 80])
    ->flash();
```

### `hardware_scanner`

| Key                   | Default            | Meaning                                            |
| --------------------- | ------------------ | -------------------------------------------------- |
| `enabled`             | `true`             | master switch (components also gate individually)  |
| `burst_threshold_ms`  | `50`               | max gap between keystrokes counted as one burst    |
| `min_barcode_length`  | `2`                | shorter bursts are treated as typing               |
| `min_fast_keys`       | `1`                | consecutive fast gaps required before a terminator counts; `3` rejects fast typists (planned 1.0 default) |
| `fallback_to_any_input` | `true`           | page-global listener may fill the first empty form input when no focused or registered field is free; `false` dispatches `qr-scan-unrouted` instead |
| `scan_timeout_ms`     | `150`              | flush window for terminator-less guns (0 disables) |
| `prevent_form_submit` | `true`             | swallow the terminator key during bursts           |
| `default_terminators` | `['Enter', 'Tab']` | gun suffix keys ending a scan                      |
| `default_scope`       | `'focus'`          | where a `QrScanner` catches wedge scans: `'focus'` (only while focused) or `'page'` (also when no scan field has focus); override with `->hardwareInput(scope:)` |

Auto-repeat keystrokes (a held-down key) never count toward a burst.

#### Hardware routing

Every wedge consumer (`QrScanner`, `QrHardwareScannerListener`, `QrCollector`, `QrScanSequence`) registers with one page-wide input router, so each burst is handled exactly once. The router picks the owner in this order:

1. the `QrScanner` whose camera modal is open;
2. the focused scan field (a focused `QrScanner`, or a focused field registered with a listener);
3. the first listening target on the page, in page order: page-scope `QrScanner` fields (`->hardwareInput(scope: HardwareInputScope::Page)`), collectors, sequences and each empty field registered with a listener (ranked by the field's own position);
4. the listener's `fallback_to_any_input`;
5. otherwise `qr-scan-unrouted`.

A field that can't take the burst (busy checking a scan, too short for its `min_barcode_length`) is skipped and the next target gets it. When the burst lands somewhere other than the focused input, the characters the gun typed into that input are removed again, unless the input (or an ancestor) has the `data-qr-ignore` attribute.

`suppressWhenGlobalListener()` and the JS `isGlobalHardwareListenerActive()` are deprecated: the router makes them unnecessary.

These values are the defaults behind every scan surface (`QrScanner`, `QrScanSequence`, `QrCollector`, `QrHardwareScannerListener`, `QrCollectAction`): each component ships them to the browser as one payload (`getScanPayload()`), and any explicit setter (`->fps()`, `->sound()`, `->hardwareScanner()`, …) wins over config.

The Station Listener adds its own routing switch:

```php
QrHardwareScannerListener::make(['sku'])
    ->fallbackToAnyInput(false); // no first-empty-input fallback; dispatch qr-scan-unrouted instead
```

### `feedback`

| Key                   | Default  |
| --------------------- | -------- |
| `sound`               | `true`   |
| `beep_frequency`      | `880` Hz |
| `beep_duration_ms`    | `80`     |
| `vibrate`             | `true`   |
| `vibrate_duration_ms` | `100`    |
| `success`             | `['sound' => true, 'vibrate' => [100], 'flash' => true]` |
| `error`               | `['sound' => true, 'vibrate' => [80, 60, 80], 'flash' => true]` |

### `camera`

| Key                  | Default | Meaning                                                                                  |
| -------------------- | ------- | ---------------------------------------------------------------------------------------- |
| `fps`                | `25`    | decode attempts per second; auto-degrades to 12 when unrestricted and not explicitly set |
| `qrbox`              | `250`   | _maximum_ decode-box edge; the real box scales to the viewfinder (wide band for 1D)      |
| `prefer_rear_camera` | `true`  | rear heuristic; the remembered `localStorage` choice wins                                |

### `scan`

| Key                            | Default   | Meaning |
| ------------------------------ | --------- | ------- |
| `cooldown_ms`                  | `1500`    | repeat window for fields with server checks (`cooldown()`) |
| `clear_on_reject`              | `true`    | clear the field on a rejected live scan (`clearOnReject()`) |
| `continuous.display`           | `'empty'` | continuous fields after an accepted scan: `'empty'` or `'last_code'` (`continuousDisplay()`) |
| `continuous.pause_on_reject`   | `false`   | `false` shows the error and keeps scanning; `true` pauses until dismissed (`pauseOnReject()`) |
| `continuous.keep_camera_open`  | `false`   | `false` closes the camera after each scan (`keepCameraOpen()`) |
| `continuous.cooldown_ms`       | `300`     | repeat window for continuous fields (`cooldown()` wins) |
| `sync_options`                 | `'morph'` | when the browser picks up server option changes: `'morph'` (every re-render) or `'refresh'` (only on a `qr-scanner-refresh` / `qr-sequence-refresh` event); `syncOptions()` per field |

### `sequence`

| Key                  | Default          | Meaning |
| -------------------- | ---------------- | ------- |
| `on_invalid`         | `'jump_to_step'` | a failed `validateAllUsing()`: `'jump_to_step'` or `'restart'` (`onInvalidSequence()`) |
| `auto_submit`        | `false`          | call `submit_method` once the sequence completes (`autoSubmit()`) |
| `submit_method`      | `'submit'`       | the Livewire method `autoSubmit()` calls |
| `reset_after_submit` | `false`          | clear non-sticky steps after a successful submit (`resetAfterSubmit()`) |
| `continuous`         | `false`          | start over after every completed sequence (`continuous()`) |
| `repeat_complete`    | `'advance'`      | a full `repeat(n)` step: `'advance'` or `'wait'` (`onRepeatComplete()`) |
| `finish_code`        | `null`           | the scan that finishes a waiting or open-ended repeat step (`finishWith()`) |

### `generator`

`size` (300), `margin` (2), `format` (`svg`), `foreground_color`, `background_color`, `error_correction` (`M`), `cache_ttl` (86400; `null` = forever, `false` = L2 off), `cache_store` (`null` = default store).

---

## Browser Events

| Event                     | Detail                        | Fired when                         |
| ------------------------- | ----------------------------- | ---------------------------------- |
| `qr-scanned`              | `{ value, field, nextField, source, recordKey?, mode }` | any accepted `QrScanner` scan |
| `qr-scan-accepted`        | `{ value, field, source, recordKey, mode }` | a server-checked scan passed |
| `qr-scan-rejected`        | `{ message, value?, field?, source?, mode? }` | a server-checked scan failed, or a `rejectWhen()` predicate matched |
| `qr-scan-duplicate`       | `{ value, field, source, mode }` | same code again within the field's cooldown |
| `qr-hardware-scanned`     | `{ value, field }`            | page-global hardware burst         |
| `qr-scan-unrouted`        | `{ value }`                   | page-global burst found no target field |
| `qr-scan-routed`          | `{ value, target, kind, reason, burstId }` | the input router delivered a burst; `reason` is `modal`, `focus`, `first-listening`, `fallback` or `queued` |
| `qr-scan-dropped`         | `{ value, field, source }`    | a continuous field was busy and already held one queued scan |
| `qr-scan-paused`          | `{ field }`                   | a continuous field with `pauseOnReject()` paused after a rejection |
| `qr-scan-resumed`         | `{ field }`                   | the operator resumed a paused field |
| `qr-sequence-step`        | `{ field, value, index }`     | each container step                |
| `qr-sequence-completed`   | `{ results }`                 | last container step                |
| `qr-sequence-edited`      | `{ field, value }`            | operator edits a step              |
| `qr-sequence-rejected`    | `{ field, value, message, source }` | a checked step rejected a scan |
| `qr-sequence-invalid`     | `{ field, message, values }`  | `validateAllUsing()` failed on completion |
| `qr-collector-item-added` | `{ code }`                    | each batch-collector scan          |

`mode` is `'single'` or `'continuous'` (see [Continuous mode](scanning.md#continuous-mode)).

Events you **send** to control a scanner or sequence (`qr-scanner-feed`, `qr-scanner-pause`, `qr-sequence-go-to`, …) are listed in [Runtime control](scanning.md#window-events).

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
- **Look and markup**: `extraAttributes()`, `fi-qr-*` classes, CSS variables, render hooks and per-part views. See [customizing.md](customizing.md#styling).
- **Print**: wrap QR images in `.filament-qr-label-sheet` / `.filament-qr-label` for a three-per-row shelf-label print sheet (camera UI auto-hides in print).
- **Generator look**: `size()`, `margin()`, `color()`, `backgroundColor()`, `errorCorrection()`, `caption()`, `withText()`, `logo()` — see [§6](generating.md#6-qr-code-generator-components). `withText()` / `logo()` force PNG output.

---

## Extending

Custom lookups, code normalization presets, check stages (`pipe()`), step hooks, macros, `make:qr-scan-field`, render hooks, CSS variables and per-part view overrides are covered in [Extending and customizing](customizing.md).

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

### Runtime control

Change options at runtime, feed codes, pause, resume and reset scanners and sequences from page code, drive a sequence from PHP (`goToStep()`, `resetSequence()`, `setRepeatCount()`), make steps conditional with `ScanStep::when()`, and register your own inputs with `window.FilamentQrCode.router`. See [Runtime control](scanning.md#runtime-control).

### Common payload builders

`QrPayload::wifi()`, `::vcard()`, `::mailto()`, `::sms()`, `::geo()` build correctly escaped payloads — see [Common payloads](generating.md#common-payloads-without-hand-escaping).

### Validate on submit

`QrScanner::scanRules([...])` adds submit-time rules for the field; `QrCollector::distinctItems()` rejects duplicates server-side to match `->allowDuplicates(false)` in the browser.
