# P2.1: Runtime control for the scanner and the sequence

Status: approved by the owner on 2026-10-20 (roadmap row P2.1).
Branch: `feat/p2-runtime-control`. One PR: spec, plan, code, docs, dist.

## Problem

The browser side of `QrScanner` and `QrScanSequence` reads its options once, when Alpine
initialises the component. Everything after that is frozen:

- `PosCheckout` has to wrap its form in `wire:key="pos-scanner-{options}"` so a switch
  change re-creates the scanner, which drops the camera and any queued scan.
- `QrScanSequence` renders `wire:ignore`, so its step payload never refreshes. In
  `GoodsReceiving` the repeat counter keeps showing `k / 2` after the quantity changes to 5.
- Page code has no supported way to feed a code, pause, resume, reset or reconfigure a
  mounted component. The only hooks are the internal `qr-scan-deliver` DOM event and
  `qr-sequence-editable`.
- The page-wide input router is not public, so a custom input cannot take part in
  hardware-scanner routing.
- An optional step only gives way to a following non-repeat step, which forced
  `GoodsReceiving` to put the lot before the SKU. There are no conditional steps.
- `QrScanSequence::make()` never runs `setUp()`, so `default([])` and
  `configureUsing()` never apply.

## Goals

1. Server-side option changes reach the browser automatically on a Livewire re-render
   (default), or only on an explicit refresh (opt-in, per config and per field).
2. A documented control API for page code, in two forms: a per-element API, and window
   events aimed at one field.
3. Option changes never interrupt a scan in progress. The scan finishes first.
4. The sequence browser state follows the server: step payload (labels, repeat `n`,
   active steps) and values.
5. Page-level PHP methods to drive a sequence: go to a step, reset, set a repeat count.
6. Flexible step order: optional steps can give way to a repeat step, and
   `ScanStep::when()` makes a step conditional.
7. A public input router: `FilamentQrCode.router.register()`.
8. Existing apps keep working unchanged.

Out of scope: lookups and view hooks (P2.2), example app changes (P2.3).

## Design

### 1. Options travel with every render

Each component root carries its full payload in a `data-qr-options` attribute (JSON).
For the scanner, that's the `x-data` element. For the sequence, it's a new wrapper
`<div data-qr-options>` around the `wire:ignore` element, so Livewire can morph the
attribute while the scanning UI underneath stays untouched.

On `init()`, the component observes that attribute with a `MutationObserver`.

- **`morph` (default):** every attribute change is applied with `setOptions()`.
- **`refresh`:** changes are ignored until the page sends a refresh event
  (`qr-scanner-refresh` or `qr-sequence-refresh`) or calls `el.qrScanner.refresh()`. Only
  then is the attribute re-read.

Config: `qr-code.scan.sync_options` (`'morph'` | `'refresh'`, default `'morph'`).
Per field: `->syncOptions(OptionsSync::Refresh)`, which also accepts a string or a closure.
New enum: `Mmuqiitf\FilamentQrCode\Enums\OptionsSync`.

Unchanged payloads are ignored: the component compares JSON strings, so a re-render that
changes nothing costs nothing.

### 2. Applying options without interrupting a scan

All options live in one reactive `options` object. The code reads `this.options.x` and
never uses a captured constant. `setOptions(partial)` merges `partial` into the
*desired* options. Applying them follows these rules:

| Situation | When new options apply |
| --- | --- |
| Idle | Immediately. |
| Server check in flight (`isChecking`) | Right after the check settles, before the queued scan is replayed. |
| Camera open | Non-camera options apply immediately (or after the check). Camera options (`fps`, `qrbox`, `formats`, `preferRearCamera`) apply on the next camera start. A `formats` change rebuilds the decoder instance on that next start. |
| Sequence round in progress (some steps have values) | Step *structure* (the list of step keys) waits until the round completes or resets. Everything else applies as soon as no check is running: labels, repeat counts, active flags, feedback, camera rules as above. This is what lets the `k / n` counter follow a quantity change mid-item. |

Hardware detection options re-register the field with the input router once nothing is
being checked. `hardwareScanner: false` unregisters it.

### 3. Control API

**Per element.** On init, the component attaches an API object to its root element.
For the scanner, the root is the `[data-field-name]` x-data element; for the sequence,
it's the `[data-qr-sequence]` x-data element.

```js
const scanner = document.querySelector('[data-field-name="sku"]').qrScanner;
await scanner.feed('PRD-1001');     // same path as a scan; source defaults to 'hardware'
scanner.pause();                    // stop accepting scans (camera paused too)
scanner.resume();
scanner.reset();                    // clear value, rejection message, queued scan, dedupe memory; resume
scanner.setOptions({ mode: 'continuous', pauseOnReject: true });
scanner.refresh();                  // re-read data-qr-options now
scanner.getOptions(); scanner.getState();   // { value, isPaused, isChecking, isScanning }

const sequence = el.qrScanSequence;
await sequence.feed('PO-7001');     // into the current step
sequence.goToStep('lot');
sequence.reset();                   // or reset({ values }) to seed values
sequence.pause(); sequence.resume();
sequence.setOptions({...}); sequence.refresh();
sequence.getOptions(); sequence.getState();  // { currentStep, values, isPaused, isChecking }
```

Inside Alpine, `$el.closest('[data-qr-scanner]').qrScanner` works the same way. The
scanner root also gets the `data-qr-scanner` attribute.

`feed()` sources are `camera`, `hardware`, `upload` and `manual`. The default is
`hardware` (a fed code is a scan from some device, so optional steps can still give way);
anything unknown becomes `manual`.

**Window events** carry a `field` key that selects the target:

| Event | `detail` |
| --- | --- |
| `qr-scanner-feed` | `{ field, code, source? }` |
| `qr-scanner-pause`, `qr-scanner-resume`, `qr-scanner-reset`, `qr-scanner-refresh` | `{ field }` |
| `qr-scanner-set-options` | `{ field, options }` |
| `qr-sequence-feed` | `{ field, code, source? }` |
| `qr-sequence-go-to` | `{ field, step }` |
| `qr-sequence-reset` | `{ field, values? }` |
| `qr-sequence-pause`, `qr-sequence-resume`, `qr-sequence-refresh` | `{ field }` |
| `qr-sequence-set-options` | `{ field, options }` |
| `qr-sequence-set-repeat` | `{ field, step, count }` (browser display only; see below) |

`field` matches when it equals the component's full state path (for example
`data.sku`), its field name (`sku`, the `data-field-name`), or its schema component
key. Without `field`, an event goes to every component of that kind on the page.
Because these are plain window events, Livewire's `$this->dispatch('qr-scanner-pause',
field: 'data.sku')` reaches them from PHP too.

### 4. Sequence state sync

The sequence payload gains `values` (the server's sequence state) and `stepState` per
step (`active`, from `when()`). When the attribute changes and no check is running:

- `steps`, `fields` (labels) and `editable` are replaced, subject to the
  round-in-progress rule above.
- With `serverCheck` on, `results` is replaced by `values` when they differ. A page that
  clears or seeds state on the server, like `GoodsReceiving::submit()`, is then shown
  correctly. If the current step became filled or inactive, the cursor moves to the
  first empty active step.

### 5. Page-level sequence methods (PHP)

These are on `QrScanSequence` and called from page code, for example
`$this->form->getComponent('receipt')`:

- `goToStep(string $step): void` validates the step and dispatches
  `qr-sequence-go-to`.
- `resetSequence(bool $keepSticky = true, array $values = []): void` writes the sticky
  values (or none) merged with `$values` to state, and dispatches `qr-sequence-reset`
  with those values.
- `setRepeatCount(string $step, ?int $count): void` stores a per-page override that wins
  over `repeat()`, and dispatches a refresh so the counter updates. The override lives
  in the session, keyed by the Livewire component id, the sequence key and the step, so
  server checks on later requests see it. `null` clears it. It isn't cleared on reset:
  it's a setting, not a value.

The events are dispatched through the Livewire component (`$livewire->dispatch(...)`),
so they arrive as window events on the next response.

### 6. Step order

**Optional steps give way to repeat steps.** When a live scan fails an optional step's
check, the sequence tries the next *active* step. Repeat steps now qualify; for them it
runs the repeat path (full, duplicate, then checks). On success the optional step is
reported in `skipped`, as before.

**`ScanStep::when(Closure $condition)`** makes a step conditional. The closure is
evaluated by the sequence component with `$values` (the current sequence values) and
`$step` injected, and must return a bool. For example:
`->when(fn (array $values) => str_starts_with($values['sku'] ?? '', 'BATCH-'))`.

An inactive step:

- is skipped when finding the next step;
- is not required for completion (`allRequiredFilled`, `isSequenceComplete` for missing
  keys, and the form submit rules);
- rejects a scan aimed straight at it with `step_inactive`, and the cursor moves on;
- has its value dropped when the step turns inactive during a round, so stale data never
  completes a set;
- is greyed out in the UI via `stepState`.

Conditions are evaluated in step order against the values at that moment. A closure
that throws counts as active (fail open). Locally it logs a warning, like `repeat()`.

### 7. `setUp()` in `make()`

`QrScanSequence::make()` calls `$static->configure()`, which runs `setUp()` and any
`configureUsing()` callbacks. `default([])` then applies to sequences with a state path:
`fill()` writes `[]` instead of `null`, and every reader already treats both as empty.

### 8. Public input router

`window.FilamentQrCode.router` is the shared page router, and `createInputRouter` is
exported for tests. `register(target)` returns an unregister function. The target shape
is documented: `id`, `element`, `name`, `kind: 'custom'`, `scope: 'focus' | 'page'`,
`detection`, `accept(code)`, `hold(code)`, `ownsFocus(element)`, `deliver(code, context)`,
`candidates()`.

## Compatibility

- The default `morph` mode only changes behaviour when the server payload changes
  between renders. Before, that change was silently ignored. Apps that relied on the old
  freeze can set `sync_options => 'refresh'`.
- All new payload keys are additive. `when()` defaults to always active.
- The optional-skip widening only applies where the old code rejected the scan, so a
  scan that passed before still passes the same way.
- The `wire:key` re-create pattern keeps working, but is no longer needed.

## Tests (TDD, named after the roadmap)

- Vitest: "setOptions switches mode without re-creating the scanner"; morph observer
  applies payload changes, refresh mode waits for the event; options deferred during a
  check; formats rebuild on next camera start; per-element API and targeted window
  events; router exposed on `window.FilamentQrCode`.
- Vitest: "changing quantity updates repeat k/n"; values sync; structure deferred mid-round;
  go-to / reset / feed events.
- Pest: "changing quantity updates repeat k/n" (payload); "optional lot skips into
  serials"; `when()` skip, completion and rejection; `goToStep` / `resetSequence` /
  `setRepeatCount` dispatch and state; `make()` runs `setUp()`; `syncOptions` config and
  per-field override in the payload; `data-qr-options` rendered.
- Example app (local only, not committed): PosCheckout without `wire:key`, GoodsReceiving
  counter and lot-after-SKU order, existing feature and browser suites.
