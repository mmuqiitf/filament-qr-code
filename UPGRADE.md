# Upgrade guide

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
