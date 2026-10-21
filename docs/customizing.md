# Extending and customizing scan fields

Everything here is opt-in. A field that uses none of it behaves and renders exactly as before.

Worked examples: [POS checkout](recipes/pos.md) · [Goods receiving](recipes/warehouse.md) · [Work-order station](recipes/manufacturing.md) · [Label generator page](recipes/generator-page.md) · [Custom inputs](recipes/custom-input.md) · [Styling](recipes/styling.md).

## Find records yourself: `lookupUsing()`

```php
QrScanner::make('sku')
    ->lookupUsing(fn (string $code) => Http::get("https://erp.test/items/{$code}")->json() ?: null);
```

The resolver replaces `lookup()` on that field. Return the record as a model, an array or an object. Return `null` or `false` to reject the scan with the usual "no record" message. The record then reaches `onScanned()`, `validateUsing()` and your stages. `ScanResult::$record` is typed `mixed`, and `recordKey()` reads `getKey()`, `id` or `key`.

To run the resolver only when the model lookup finds nothing, pass `fallback: true`:

```php
->lookup(Product::class, 'sku')
->lookupUsing(fn (string $code) => LegacyProduct::firstWhere('code', $code), fallback: true)
```

`lookup()` also takes closures for its model and column, for example `->lookup(fn (Get $get) => $get('type') === 'box' ? Box::class : Product::class)`.

Sequence steps work the same way. The resolver gets `$code`, `$values` (the other steps' values) and `$step`:

```php
ScanStep::make('serial')->lookupUsing(fn (string $code, array $values) => Serial::forPo($values['po'])->firstWhere('number', $code))
```

## Clean codes: `normalizeWith()` and `normalizeUsing()`

```php
QrScanner::make('sku')
    ->normalizeWith(uppercase: true, stripPrefix: [']C1', 'SKU:'])   // trim is on by default
    ->normalizeUsing(fn (string $code) => str_replace(' ', '-', $code));
```

- `normalizeWith(trim: true, uppercase: false, stripPrefix: ...)` presets run in the browser too, so duplicate detection and step matching see the cleaned code. They run trim first, then strip the first matching prefix, then uppercase.
- `normalizeUsing()` runs on the server after the presets, before rules, lookup, closures and stages.
- On sequences, `normalizeWith()` applies to every step. Then `normalizeStepUsing()` runs, then the step's own `ScanStep::normalizeUsing(fn (string $code, array $values) => ...)`.

## Custom check stages: `pipe()`

```php
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;

final class RejectOutOfStock implements ScanCheckStage
{
    public function __construct(private Inventory $inventory) {}   // resolved from the container

    public function check(ScanCheckContext $context): bool|string|null
    {
        return $this->inventory->has($context->record) ?: 'No stock left.';
    }
}

QrScanner::make('sku')->lookup(Product::class, 'sku')->pipe(RejectOutOfStock::class);
ScanStep::make('sku')->pipe(new RejectOutOfStock(app(Inventory::class)));
```

Stages run after the built-in rules, the lookup and the `validateUsing()` closures, in the order you add them. The first failing stage stops the scan. `true`/`null` passes, `false` rejects with the default message, and a string rejects with that message. A stage that throws rejects the scan as "could not be checked" and is reported. `ScanCheckContext` carries `code`, `source`, `record`, `component`, and for sequences also `step` and `values`. Stages also re-run when the form is submitted.

There is no config list of global stages. Use Filament's `configureUsing()` instead:

```php
// AppServiceProvider::boot()
QrScanner::configureUsing(fn (QrScanner $scanner) => $scanner->pipe(RejectOutOfStock::class));
```

## Step closures get `$get`, `$values` and `$step`

`label()`, `optional()`, `sticky()`, `allowDuplicates()` and lookup model/column closures on a `ScanStep` are evaluated by the sequence. That gives them Filament's usual injections (`$get`, `$set`, `$livewire`, `$record`) plus `$step` and, if they ask for it, `$values`:

```php
ScanStep::make('lot')->optional(fn (array $values) => ! Product::find($values['sku'] ?? null)?->tracksLots)
ScanStep::make('box')->label(fn (Get $get) => $get('mode') === 'inbound' ? 'Inbound box' : 'Box')
```

While `$values` are being read, a closure that asks for them again gets `[]`, so labels that read values can't recurse.

## Step hooks: `onStepAccepted()` / `onStepRejected()`

```php
QrScanSequence::make([...])
    ->onStepAccepted(fn (string $step, ScanResult $result, array $values) => Log::info("{$step}: {$result->code}"))
    ->onStepRejected(fn (string $step, ScanResult $result) => Notification::make()->danger()->title($result->message)->send());
```

Hooks are for side effects only. Their return value is ignored, and they can't change whether a scan passes or its message. An exception thrown in a hook is reported and doesn't affect the scan. They get `$step`, `$result`, `$code`, `$record`, `$source` and `$values`.

## Richer events

`ScanAccepted` and `ScanRejected` carry `statePath`, `livewireClass` and `livewireId`. Sequence step events also carry `sequence` (the sequence state path) and `values`. Their `statePath` is the step's own path, for example `data.receipt.sku`. `ScanSequenceCompleted` carries `livewireClass` and `livewireId`. The `QrCodeScanned` context gains `state_path`. All new fields are optional constructor arguments, so existing listeners keep working.

## Macros

`QrScanner`, `QrScanSequence`, `QrCollector` and `ScanStep` are macroable:

```php
QrScanner::macro('sku', fn () => $this->lookup(Product::class, 'sku')->normalizeWith(uppercase: true));
ScanStep::macro('serial', fn () => $this->repeat()->allowDuplicates(false));
```

## Reusable field classes: `make:qr-scan-field`

```bash
php artisan make:qr-scan-field SkuScanner              # extends QrScanner
php artisan make:qr-scan-field ReceivingSequence --sequence
php artisan vendor:publish --tag=filament-qr-code-stubs  # customise the stubs
```

This creates `app/Filament/Forms/Components/{Name}.php` with a `setUp()` stub. It refuses to overwrite an existing file unless you pass `--force`. Published stubs in `stubs/filament-qr-code/` take precedence over the package's.

## Styling

- **Extra attributes**: `->extraAttributes(['class' => 'my-scanner', 'data-test' => 'sku'])` merges onto the root of the scanner, sequence and collector.
- **Classes**: every part has a stable class, for example `fi-qr-scanner`, `fi-qr-scanner-input`, `fi-qr-scanner-modal`, `fi-qr-sequence-step`, `fi-qr-sequence-step-chip` and `fi-qr-collector-items`, plus a `data-qr-part` attribute such as `sequence.step-chip`.
- **CSS variables** (each defaults to the old value):

  | Variable | Default |
  |---|---|
  | `--fi-qr-viewfinder-bg` | `#000` |
  | `--fi-qr-viewfinder-radius` | `0.75rem` |
  | `--fi-qr-reticle-color` | `#22c55e` |
  | `--fi-qr-reticle-size` | `220px` (resized at runtime to the decoder crop) |
  | `--fi-qr-laser-color` | `#22c55e` |
  | `--fi-qr-flash-success` | `rgb(34 197 94)` |
  | `--fi-qr-flash-danger` | `rgb(239 68 68)` |

## Render hooks

```php
use Filament\Support\Facades\FilamentView;
use Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook;

FilamentView::registerRenderHook(QrCodeRenderHook::SEQUENCE_STEPS_AFTER, fn () => view('receiving.legend'), scopes: 'data.receipt');
```

Available hooks: `SCANNER_START`, `SCANNER_END`, `SCANNER_MODAL_START`, `SCANNER_MODAL_END`, `SEQUENCE_START`, `SEQUENCE_END`, `SEQUENCE_STEPS_BEFORE`, `SEQUENCE_STEPS_AFTER`, `SEQUENCE_STEP_HEADER`, `COLLECTOR_START` and `COLLECTOR_END`. Scopes are the Livewire component class and the field state path. `SEQUENCE_STEP_HEADER` renders once into the Alpine step template, so it repeats on every step. Keep it static, or use Alpine expressions such as `x-text="f.label"`.

## Overriding parts of a view

The views are split into one file per part. Publish them with `php artisan vendor:publish --tag=filament-qr-code-views`, then keep only the files you change under `resources/views/vendor/filament-qr-code/components/`:

- `scanner/`: `input`, `camera-button`, `status`, `modal`, `modal-camera`, `modal-actions`
- `sequence/`: `header`, `camera-select`, `status`, `camera`, `steps`, `step`, `step-chip`, `step-value`
- `collector/`: `header`, `camera`, `items`

Parts share the parent view's variables (`$scanPayload`, `$statePath`, ...) and the Alpine scope. Keep `x-` bindings and `data-qr-*` attributes the JavaScript relies on.
