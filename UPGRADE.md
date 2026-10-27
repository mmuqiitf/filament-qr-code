# Upgrade guide

## From 0.6 to 1.0

1.0 removes everything 0.5 and 0.6 deprecated and changes one default. Search your app for each name below; most apps only need steps 1 and 2.

### 1. Rebuild the assets

```bash
composer update mmuqiitf/filament-qr-code
php artisan filament:assets
```

### 2. `min_fast_keys` now defaults to 3

A keyboard-wedge burst now needs three consecutive fast keystrokes before a terminator counts as a scan (it was 1). This keeps fast human typing from being taken for a scan. Every handheld gun we know of types much faster than that. If a slow or Bluetooth gun's short codes are missed, go back to the old value:

```php
// config/qr-code.php
'hardware_scanner' => [
    'min_fast_keys' => 1,
],

// or per component
QrScanner::make('sku')->minFastKeys(1);
```

The JS defaults (`createHardwareScannerHandler()`, `FilamentQrCode.router`) changed the same way.

### 3. Route name `filament-qr-code.image` is removed

Use `filamentQrCode.image`:

```php
// Before
URL::signedRoute('filament-qr-code.image', $parameters);

// After
URL::signedRoute('filamentQrCode.image', $parameters);
```

### 4. `suppressWhenGlobalListener()` and `isGlobalHardwareListenerActive()` are removed

The input router has given every burst exactly one owner since 0.5, so these did nothing. Delete the calls:

- `->suppressWhenGlobalListener(...)` and `isSuppressedWhenGlobalListenerActive()` on every scan surface;
- the `suppressWhenGlobalListenerActive:` argument of `hardwareScanner(...)` (if you passed `minFastKeys` positionally, it is now the 7th argument; use the named argument);
- `window.FilamentQrCode.isGlobalHardwareListenerActive()` in your JS;
- the `suppressWhenGlobalListenerActive` / `suppressWhenGlobal` payload keys, if you read them.

### 5. `QrScanSequence::statePathPrefix()` is removed

The flat per-step paths (`data.step`, `data.employee`, …) now always follow the schema's `statePath()`, so they can no longer point outside the form. Remove `->statePathPrefix(...)`; the "scans will be lost on submit" warning (`prefixMismatchWarning()`, `ui.sequence_prefix_mismatch`) is gone with it. If you set a prefix different from the schema `statePath()`, give the sequence its own `->statePath()` and read it with `mergeSequenceState()`.

### 6. `LogQrCodeScan` is now `LogQrCodeScanListener`

```php
// Before
use Mmuqiitf\FilamentQrCode\Listeners\LogQrCodeScan;

// After
use Mmuqiitf\FilamentQrCode\Listeners\LogQrCodeScanListener;
```

Only code that references the class (to unregister it, or in an `Event::fake()` assertion) needs the change; the package registers it for you.

### 7. `QrCodeDisplay::getQrSvgOrDataUri()` is removed

It returned the same value as `getQrDataUri()`. Rename the call, including in published `qr-code-display` views (`$getQrSvgOrDataUri()` becomes `$getQrDataUri()`):

```php
// Before
$uri = $display->getQrSvgOrDataUri();

// After
$uri = $display->getQrDataUri();
```

### 8. `QrCodeService` subclasses

Only if you extend `QrCodeService`: the protected `applyLogo()`, `applyTextOverlay()`, `readPersistentCache()` and `writePersistentCache()` methods and the protected static `$renderCache` property are gone, with no shims. Override `generate()` or wrap the service instead.

### `@internal` members

Some classes and methods are now tagged `@internal` (see [Public API and `@internal`](docs/reference.md#public-api-and-internal)). They still work, but they are no longer covered by semver. If you call one from app code, move to the documented API.

### `bacon/bacon-qr-code` 3.0.4 or newer

The package now requires `bacon/bacon-qr-code` `^3.0.4`. If your lock file has an older 3.0.x, `composer update mmuqiitf/filament-qr-code --with-dependencies` updates it.

### Behaviour changes

- An inline `QrCollector` now calls `handleCollectorScan($code)` on the host Livewire component when it defines that method, as documented. Before 1.0 it always dispatched the `qr-collector-item-added` window event instead. The event is no longer dispatched when the hook exists, so code that forwarded it to the same method is now dead; remove it.

- `generator.cache_ttl => false` now really turns the persistent render cache off. Before 1.0 it cached forever, like `null`. If you set `false` and relied on that, set `null` instead.

- `validateAllUsing()` now also runs when a counted step (`repeat(n)`) is finished early with `finishWith()`. A failing whole-set check reports its own message and jumps to (or restarts from) the invalid step, like a failed completion, instead of the generic "needs n codes" message. When the whole set passes, the count message stays.
- `->pipe()` stages (and every step check) see the same `$values` live and on submit: the active steps before the step, plus the codes scanned before this one in a repeat step. On submit they used to see the whole form.
- The camera stops while the page is hidden (a locked phone, a background tab) and starts again when the page is visible, unless the operator stopped it in between.
- The scanner modal's overlay is now positioned by the package stylesheet, so it covers the viewport even when the panel theme does not `@source` the package views.
- Camera errors are translated and no longer show the raw browser error text. If you published `ui.php`, add the new keys (`camera_*`, `announce_*`; see the [changelog](CHANGELOG.md)); missing keys fall back to the key name. `ui.loading_camera` is gone.
- The collector, collect-action modal and scan sequence announce accepted and rejected scans to screen readers. If you override the `qr-collector`, `qr-collector-modal` or `qr-scan-sequence` views, include `filament-qr-code::components.announcer`. The sequence rejection text is no longer `role="alert"`.

## From 0.5 to 0.6

Most apps only need the first two steps.

### 1. Rebuild the assets

```bash
composer update mmuqiitf/filament-qr-code
php artisan filament:assets
```

The scanner and sequence scripts changed (runtime control, the shared input router). Stale assets in `public/` keep the old behaviour.

### 2. Republish customised views

If you published the package views, republish them and re-apply your changes:

```bash
php artisan vendor:publish --tag=filament-qr-code-views --force
```

The views are now split into one file per part (`components/scanner/*`, `components/sequence/*`). The scanner's `x-data` no longer embeds the options (they're on `data-qr-options`), and the sequence has a new wrapper `<div data-qr-options>`. An old full-view override keeps the old markup and will miss runtime control.

Keep only the files you changed under `resources/views/vendor/filament-qr-code/components/`. Before reaching for a view, check whether `extraAttributes()`, the `--fi-qr-*` CSS variables or a `QrCodeRenderHook` covers it: those survive upgrades. See [the styling recipe](docs/recipes/styling.md).

### 3. `ScanResult::$record` can be any value

`$record` is now typed `mixed`: `lookupUsing()` may return an array, a DTO or a model. If your code reads `$result->record->getKey()`, use `$result->recordKey()`: the model key, else `id` or `key` from an array or object record:

```php
// Before
$id = $result->record?->getKey();

// After
$id = $result->recordKey();
```

Code that only uses `lookup(Model::class, ...)` still gets a model.

### 4. `normalizeUsing()` runs before the checks

A scanner's `normalizeUsing()` used to clean only the stored value. It now also runs on the server before the rules, the lookup and stages, so a lookup sees the normalized code. If you relied on the raw code in a rule, normalize inside the rule instead, or use `normalizeWith()` presets, which run identically in the browser and on the server.

### 5. Route name `filament-qr-code.image` is deprecated

The signed image route is `filamentQrCode.image`. The kebab-case `filament-qr-code.image` still generates the same URL but is a deprecated alias and will be removed in 1.0:

```php
// Before
URL::signedRoute('filament-qr-code.image', $parameters);

// After
URL::signedRoute('filamentQrCode.image', $parameters);
```

### Smaller changes

- An optional step whose check fails now also gives way to a following repeat step (`po, sku, lot?, serials` works). Workarounds that put the optional step last can go.
- Scanner options (`continuousDisplay()`, `pauseOnReject()`, `repeat()` counts, …) now follow the server on every re-render. Workarounds that re-mount the field with a changing `wire:key` can go.
- `QrScanSequence::make()` runs `setUp()` and `configureUsing()` callbacks; sequences with a state path fill to `[]`.
- `ScanAccepted` / `ScanRejected` / `ScanSequenceCompleted` gained optional fields (`statePath`, `livewireClass`, `livewireId`, `sequence`, `values`). Listeners that build these events themselves (tests) keep working: the new constructor arguments are optional.
