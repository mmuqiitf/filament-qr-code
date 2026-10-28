# Reference

> Back to [README](../README.md) · [Scanning](scanning.md) · [Batch collecting](collecting.md) · [Generating](generating.md) · [Customizing](customizing.md)

Recipes: [POS checkout](recipes/pos.md) · [Goods receiving](recipes/warehouse.md) · [Work-order station](recipes/manufacturing.md) · [Label generator page](recipes/generator-page.md) · [Custom inputs](recipes/custom-input.md) · [Styling](recipes/styling.md). Upgrading from an earlier release: [UPGRADE.md](../UPGRADE.md).

Configuration, browser events and translations. How-to material lives in [customizing.md](customizing.md) and [extending.md](extending.md); every public method is in the [API map](api.md).

## Public API and `@internal`

Everything documented here and in the guides is public API and follows semver. Classes and methods tagged `@internal` in a docblock (render cache and spec classes, client payloads, the Livewire and Filament hooks a component must keep public) are not: they can change in any release, including a minor or patch release. Don't call or extend them from app code.

## Configuration

Publish the config to tune defaults (`config/qr-code.php`):

```bash
php artisan vendor:publish --tag="filament-qr-code-config"
```

Per-component options override these defaults:

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

QrScanner::make('sku')
    ->fps(25)
    ->qrbox(250) // responsive max; the decode box scales to the viewfinder
    ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
    ->preferRearCamera()
    ->cameraControls() // torch / zoom when the camera supports them; false hides them
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
| `min_fast_keys`       | `3`                | consecutive fast gaps required before a terminator counts; `3` rejects fast typists, `1` restores the 0.x behaviour |
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

These values are the defaults behind every scan surface (`QrScanner`, `QrScanSequence`, `QrCollector`, `QrHardwareScannerListener`, `QrCollectAction`): each component ships them to the browser as one payload (`getScanPayload()`), and any explicit setter (`->fps()`, `->sound()`, `->hardwareScanner()`, …) wins over config.

The Station Listener adds its own routing switch:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

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
| `controls`           | `true`  | torch / zoom controls, shown only when the running camera reports the capability         |

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

Every key of `qr-code.sequence`; each has a per-component setter that wins over config. Sequence events are in [events.md](events.md#sequence-qrscansequence).

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

`size` (300), `margin` (2), `format` (`svg`), `foreground_color`, `background_color`, `error_correction` (`M`), `cache_ttl` (86400; `null` = forever, `false` = L2 off), `cache_store` (`null` = default store), `logo_base_paths` (`null` = any readable path; a list of directories logos must live in — see [Image route security](generating.md#image-route-security)).

### `image_route`

| Key             | Default | Meaning |
| --------------- | ------- | ------- |
| `ttl`           | `null`  | minutes a lazy-preview URL stays valid (`temporarySignedRoute`); also caps `Cache-Control` `max-age` at the TTL (never above a day). `null` = never expires |
| `throttle`      | `null`  | rate limit for the image route, e.g. `'60,1'` (60 per minute per client); excess requests get 429 |
| `strict_colors` | `false` | require `#rrggbb` colours on the image route; anything else gets 422 |

### `audit`

| Key       | Default | Meaning |
| --------- | ------- | ------- |
| `enabled` | `false` | log every server-observed scan (`QrCodeScanned`) — see [Audit scans](customizing.md#audit-scans) |
| `channel` | `null`  | log channel for the audit lines; `null` uses the default stack |

---

## Events

Every browser event the package fires, every control event a page can send, and every Laravel event with its properties: see [events.md](events.md). For every extension seam in one table, see [extending.md](extending.md).

---

## Translations

All UI strings live under the `filament-qr-code::ui` translation namespace (`resources/lang/en/ui.php`, plus Indonesian in `resources/lang/id/ui.php`). Publish with:

```bash
php artisan vendor:publish --tag="filament-qr-code-translations"
```

and translate per locale.

Camera errors and screen-reader announcements are translated too: the browser components receive them from PHP, so a published `ui.php` covers them. The announcement keys take placeholders: `announce_added` (`:code`, `:count`), `announce_step_added` (`:code`, `:step`) and `announce_rejected` (`:reason`); `camera_device` takes `:id`.

### Screen-reader announcements

The collector, the collect-action modal and the scan sequence each have a visually hidden polite live region (`data-qr-part="announcer"`, `role="status"`). It reads each accepted scan ("Added ABC-1 (3 items)", "Added PO-1 to PO") and each rejection ("Rejected: Unknown PO") once; the same message is not repeated within 3 seconds. Camera errors stay in a `role="alert"` region.

---

---

## Customizing and extending

- Config defaults, translations, styling, print sheets and generator look: [customizing.md](customizing.md).
- Live-scan normalization, programmatic triggers and scan auditing: [Normalize live scans](customizing.md#normalize-live-scans), [Drive scans programmatically](customizing.md#drive-scans-programmatically), [Audit scans](customizing.md#audit-scans).
- Every extension seam in one table: [extending.md](extending.md).
