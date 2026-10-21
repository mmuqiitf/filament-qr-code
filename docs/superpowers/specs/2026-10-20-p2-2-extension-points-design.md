# P2.2: Extension points, server and views

Status: approved by the owner on 2026-10-20 (roadmap row P2.2).
Branch: `feat/p2-extension-points`. One PR: spec, plan, code, docs, dist.

## Problem

The v0.5 extensibility audit graded server extension points and view customization as partial or missing:

- Lookups only work with Eloquent (`where(column, code)->first()`). A closure can reject a code but can't supply a
  record, so API-backed or composite lookups are impossible. The model and column can't be closures.
- Live codes can't be cleaned before checks. `QrScanner::normalizeUsing()` only runs after the state is written,
  and the browser deduplicates the raw code.
- `ScanStep` closures (label, optional, sticky, allowDuplicates) run through `value()`, so they get no `$get`,
  `$livewire`, `$values` or `$step`.
- A sequence has no live per-step hook. `onStepScanned()` is programmatic-only.
- Events carry only the field name: no state path, no Livewire component.
- The check pipeline is closed: no extra stages.
- `ScanStep` isn't macroable.
- The views are monolithic: no `extraAttributes`, no semantic classes, no CSS variables, no render hooks, and no
  smaller views to override.
- There's no generator for an app's own scan field class.

## Goals and binding decisions

1. `lookupUsing(Closure)` **replaces** the model lookup on that field. `lookupUsing($fn, fallback: true)` runs it
   only when the model lookup found nothing.
2. `normalizeUsing(Closure)` runs on the server **before** rules and lookup. Built-in presets (trim, uppercase,
   strip prefix) run in the browser, so dedupe and step matching see the cleaned code, and they run again on the
   server.
3. `onStepAccepted` / `onStepRejected` are side effects only. Their return value is ignored, and an exception is
   reported and swallowed, so the result and message never change.
4. Custom check stages are added per field with `->pipe(Stage::class)` and run after the built-in rules, lookup and
   closures. There's no config list; global use goes through `configureUsing()`.
5. Views are split into publishable sub-views, one per part. There are no per-field view closures. The rendered
   output for existing apps stays the same apart from the new classes and attributes.
6. `make:qr-scan-field Name [--sequence]` generates a class extending `QrScanner` or `QrScanSequence` with a
   `setUp()` stub. The stubs are publishable.
7. Existing apps are unaffected: every addition is opt-in or additive.

## Design

### 1. Lookups

`InteractsWithScans` (QrScanner) and `ScanStep` gain:

```php
->lookup(string|Closure $model, string|Closure $column = 'code', ?Closure $modifyQueryUsing = null)
->lookupUsing(Closure $resolver, bool $fallback = false)
```

- `$model` and `$column` closures are evaluated by the owning component when the check runs.
- `lookupUsing` resolvers receive `$code`, `$source` (plus `$values` and `$step` for steps) and the usual Filament
  injections. They return the record (any value: a model, an array, a DTO) or `null` / `false` for "not found",
  which rejects with `record_not_found`.
- **Replace (default):** with `lookupUsing()`, the model lookup is skipped even when `lookup()` is also set.
- **Fallback:** with `fallback: true` the model lookup runs first; the resolver runs only when it found nothing.
  Without `lookup()`, a fallback resolver simply runs.
- `ScanResult::$record` widens from `?Model` to `mixed`. `record_key` is the model key, else `id` / `key` from an
  array or object, else `null`. `onScanned(fn ($record) ...)` receives whatever the resolver returned.
- Security: the code is untrusted input. The built-in lookup keeps parameter binding; the docs say resolvers must
  too.

### 2. Normalizing

- **Server:** `QrScanner::normalizeUsing(Closure)` keeps its `afterStateUpdated` behaviour for typed state, and
  now also runs in `handleScan()` before the pipeline. It's evaluated with `$code`, `$rawValue` and `$state`.
  `ScanStep::normalizeUsing(Closure)` does the same per step, after the sequence-wide `normalizeStepUsing()`.
- **Presets:** `normalizeWith(bool $trim = true, bool $uppercase = false, string|array|null $stripPrefix = null)`
  on `QrScanner` and `QrScanSequence`. The payload key is `normalize: { trim, uppercase, stripPrefixes }`. The
  browser applies the presets before dedupe and step matching (`normalizeCode()` in `scan-pipeline.js`). The server
  applies the same presets first in `handleScan()` / `handleStepScan()`, so a client that skips them gains nothing.
  The order is trim, strip the first matching prefix, uppercase, then the closure.
- Without `normalizeWith()`, the payload has no `normalize` key and the browser behaves as today (it already trims).

### 3. Step closures with injection

`QrScanSequence::getScanSteps()` hands each step an evaluator bound to the sequence. Step closures (`label`,
`optional`, `sticky`, `allowDuplicates`, `when`, `repeat`, `finishWith`, `onRepeatComplete`, lookups, normalizers)
are evaluated with `$step` and `$values` plus Filament's own `$get`, `$set`, `$livewire`, `$component`. Values are
only computed when the closure declares a `$values` parameter, and a re-entrancy guard passes `[]` while values are
being read. That avoids the label → state → label loop. A step used outside a sequence falls back to `value()`, as
today.

### 4. Live step hooks and richer events

```php
QrScanSequence::make()
    ->onStepAccepted(fn (string $step, ScanResult $result, array $values) => ...)
    ->onStepRejected(fn (string $step, ScanResult $result, array $values) => Log::info($result->message));
```

They run after the result is final, on live scans, typed corrections and Done. They can't change the outcome: the
return value is ignored, and a `Throwable` is `report()`ed and swallowed.

`ScanAccepted`, `ScanRejected` and `ScanSequenceCompleted` gain trailing optional constructor properties:
`?string $statePath`, `?string $livewireClass`, `?string $livewireId` (strings, so the events stay serializable for
queued listeners), and for steps `?string $sequence` (the sequence state path) and `array $values`.
`QrCodeScanned::$context` gains `state_path`. Positional construction stays compatible.

### 5. Pluggable stages

```php
interface ScanCheckStage
{
    /** null or true: pass; false: reject with scan_rejected; string: reject with that message */
    public function check(ScanCheckContext $context): bool|string|null;
}
```

- `ScanCheckContext` is a readonly object with `code`, `source`, `record`, `component` (the field or sequence),
  `step` (`?ScanStep`) and `values`.
- `->pipe(string|ScanCheckStage ...$stages)` on `QrScanner` and `ScanStep`. A class string is resolved from the
  container per check, so stages get dependency injection, and it must implement the contract (otherwise
  `InvalidArgumentException` when added).
- Stages run after rules, lookup and closures, in the order added, and the first failure stops the run. Like the
  other checks they re-run on form submit (`ScanChecksRule` / `SequenceStepRule`), and a thrown exception becomes
  `scan_failed`.
- Global use: `QrScanner::configureUsing(fn (QrScanner $field) => $field->pipe(AuditStage::class))`.

### 6. Macroable

Filament components (`QrScanner`, `QrScanSequence`, `QrCollector`, `QrCodeDisplay`, `QrColumn`, `QrEntry`) are
already macroable through Filament's base `Component`. This is now documented and tested. `ScanStep` gains
`Illuminate\Support\Traits\Macroable`.

### 7. Views

- **Sub-views.** Each view becomes a thin shell that `@include`s one sub-view per part, under
  `resources/views/components/{scanner,sequence,collector}/`. They're publishable with the existing
  `filament-qr-code-views` tag, so an app overrides one part, for example `sequence/step-chip.blade.php`, without
  copying the rest. A snapshot test proves the output is unchanged: it compares against the pre-split HTML with the
  new classes, attributes and hook markers stripped.
  - Scanner: `input`, `camera-button`, `status`, `modal`, `modal-camera`, `modal-actions`.
  - Sequence: `header`, `camera`, `status`, `steps`, `step`, `step-chip`, `step-value`.
  - Collector: `header`, `camera`, `items`.
- **`extraAttributes()`** is merged onto each component root (scanner field root, sequence root, collector root).
  It's empty by default, so the output is unchanged.
- **`fi-qr-*` classes** on every part, for example `fi-qr-scanner`, `fi-qr-scanner-input`, `fi-qr-scanner-button`,
  `fi-qr-scanner-modal`, `fi-qr-status`, `fi-qr-sequence`, `fi-qr-sequence-step`, `fi-qr-step-chip` and
  `fi-qr-collector`. They're added to the existing class lists.
- **CSS custom properties** in `qr-code.css`, each with today's value as the fallback:
  - `--fi-qr-viewfinder-bg`, `--fi-qr-viewfinder-radius`, `--fi-qr-reticle-color`, `--fi-qr-reticle-size`,
    `--fi-qr-laser-color`, `--fi-qr-flash-success`, `--fi-qr-flash-danger`.
- **Render hooks** (Filament `FilamentView::renderHook`). The names are constants on
  `Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook`:
  - `SCANNER_START`, `SCANNER_END`, `SCANNER_MODAL_START`, `SCANNER_MODAL_END`
  - `SEQUENCE_START`, `SEQUENCE_END`, `SEQUENCE_STEPS_BEFORE`, `SEQUENCE_STEPS_AFTER`, `SEQUENCE_STEP_HEADER`
  - `COLLECTOR_START`, `COLLECTOR_END`
  - Scopes are the Livewire component class and the field's state path, so a hook can target one page or one
    field. Hooks render server-side, so content in `SEQUENCE_STEP_HEADER` repeats in every step header; Alpine
    expressions like `f.key` are available there.

### 8. `make:qr-scan-field`

`php artisan make:qr-scan-field SkuScanner [--sequence] [--force]` creates
`app/Filament/Forms/Components/SkuScanner.php`, a class extending `QrScanner` (or `QrScanSequence`) with a
`setUp()` stub that calls `parent::setUp()` and shows commented examples. It's a Laravel `GeneratorCommand`, so it
validates the name, refuses to overwrite without `--force`, and honours published stubs in
`stubs/qr-scan-field.stub` and `stubs/qr-scan-sequence-field.stub` (tag `filament-qr-code-stubs`).

## Compatibility

All additions are opt-in, and the default output is identical apart from added classes, data attributes and empty
hook output. The `ScanResult::$record` type widens (reads still work). Event constructors gain optional trailing
parameters. Customised published views keep working; the docs list the new sub-views for apps that want to move
to them.

## Tests (TDD)

- Pest:
  - "lookupUsing resolves from a closure and feeds onScanned", the replace and fallback semantics, and model and
    column closures.
  - `normalizeUsing` before rules and lookup, and presets on the server.
  - "step closure receives `$get`", plus `$values` / `$step` injection without recursion.
  - `onStepAccepted` / `onStepRejected` fire, can't change the result, and swallow exceptions.
  - Events carry the state path and Livewire info.
  - `pipe()` order, rejection, DI, submit re-run, and a bad class.
  - `ScanStep` macros.
  - `extraAttributes` rendered, `fi-qr-*` classes present, "render hook content appears in the step header", and
    output unchanged versus the pre-split snapshot.
  - The generator snapshot of `SkuScanner.php`, `--sequence`, and a published stub override.
- Vitest: `normalizeCode` presets; the scanner dedupes and sends the cleaned code; the sequence matches with the
  cleaned code.
- Example app, locally and not committed: feature and browser suites against the branch.
