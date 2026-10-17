# Scanning

> Back to [README](../README.md) · [Batch collecting](collecting.md) · [Generating](generating.md) · [Reference](reference.md)

Camera fields, hands-free station mode, sequential multi-field scanning, focus handoff, and the cashier POS tutorial.

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
- `scanFormat()` / `onScan()` — programmatic-only hooks; they do **not** run on live camera or hardware scans. Normalize live values with `normalizeUsing()` — see [Normalize live scans](reference.md#normalize-live-scans).
- `hardwareScanner(terminators: [...], minBarcodeLength: 2)` — burst tuning. Buffers are sanitized (STX/ETX/CR/LF stripped, mirroring `HasHardwareScanner::sanitizeScannedValue()`), IME compositions never count, and terminator-less guns flush after `scanTimeoutMs` (default 150).
- `scanRules(['min:3'])` — submit-time validation for scanned values. `rejectWhen(fn ($state) => ..., 'message')` clears bad live scans immediately and dispatches a `qr-scan-rejected` window event (with `{ message }`) so the app can notify.
- `hardwareInput(scope: HardwareInputScope::Page)` — also catch wedge scans when no scan field has focus (default `Focus`: only while focused; config `hardware_scanner.default_scope`). A focused scan field always wins.
- `nextField('other')` — focuses that field after a scan (field handoff, see §4).
- `fps()` / `qrbox()` — defaults 25 / 250. `qrbox` is a _maximum_: the actual decode box scales to the viewfinder and the green reticle follows it.
- `preferRearCamera()` — rear heuristic, but the operator's last camera choice (stored in `localStorage`) always wins.
- `allowUpload(false)` — hides the image-file fallback.
- `beepFrequency()` / `beepDuration()` / `vibrateDuration()` — feedback tuning.

The modal lists every detected camera (switching restarts the feed and is remembered) and closes automatically after a successful camera scan. Upload scans reuse the same decoder instance.

> Hardware scanner note: all hardware consumers share one page-wide input router, so a burst is never handled twice, even with a `QrScanner`, a `QrHardwareScannerListener` and a `QrCollector` on the same page. See [Hardware routing](reference.md#hardware-routing).

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

Add `QrHardwareScannerListener` anywhere in your schema. It intercepts hardware scanner bursts across the entire page, syncs Livewire state, and auto-advances focus. Each burst is routed to:

1. the focused input or textarea, otherwise
2. the first empty registered field (in the order passed to `make([...])`), otherwise
3. the first empty `text`, `search`, `number` input or textarea in any form on the page — only while `fallbackToAnyInput()` is enabled (default `true`, config `hardware_scanner.fallback_to_any_input`).

Step 3 can catch a search or filter box. Turn it off so scans only land in the focused or registered fields:

```php
QrHardwareScannerListener::make(['sku', 'quantity'])
    ->fallbackToAnyInput(false);
```

When no target is found, nothing is written and the listener dispatches a `qr-scan-unrouted` window event with `{ value }`, so the app can warn the operator:

```blade
<div x-on:qr-scan-unrouted.window="alert(`No field for ${$event.detail.value}`)"></div>
```

Fields are matched by their exact name — `data-field-name`, `name`, `id`, or a `wire:model` path equal to the name or ending in `.<name>` — so `sku` never matches `parent_sku`, and nested names such as `items.0.sku` work in `make([...])` and `nextField()`.

The interceptor buffers keystrokes and treats gaps under `burstThresholdMs` (default 50) as a scanner burst. On a terminator key with a long-enough buffer, it `preventDefault()`s (no accidental submit), sanitizes, and routes the value — the delivery point (field, sequence, collector, or this listener) then plays the single confirmation beep, so one burst always yields one beep. Tune `terminators` and `minBarcodeLength` to your gun's suffix.

Held-down keys (keyboard auto-repeat) never count toward a burst, so holding a key and pressing Enter is not a scan. For keyboard-heavy forms, require more consecutive fast keystrokes before a terminator counts with `minFastKeys()` (default `1`, config `hardware_scanner.min_fast_keys`; `3` rejects fast human typing):

```php
QrScanner::make('sku')
    ->minFastKeys(3);

// or together with the other interceptor options
QrScanner::make('sku')
    ->hardwareScanner(terminators: ['Enter'], minBarcodeLength: 4, minFastKeys: 3);
```

`hardwareScanner(...)` resets every interceptor option to its signature default, so pass all the options you care about in one call (or use the dedicated setters such as `minFastKeys()` and `scanTimeout()`).

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

`statePathPrefix()` (default `data`) is what keeps the container bound to your form — set it to whatever `statePath()` your schema uses. Live values are normalized with `normalizeStepUsing()` (applied on read/merge) — see [Normalize live scans](reference.md#normalize-live-scans); `scanFormat()` / `onStepScanned()` are programmatic-only — see [Drive scans programmatically](reference.md#drive-scans-programmatically).

#### Reading sequence state

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

The container writes via `$wire.set`, syncing both the component state and the legacy prefix paths — so read with `mergeSequenceState()` and validate with `isSequenceComplete()`, never a hand-rolled `array_merge` over `$this->data`. Without `->statePath()`, `getSequenceState()` falls back to the legacy paths. `getMissingSequenceKeys()` lists what's still empty.

#### Correcting a scan (unedited vs edited values)

Every step is a plain input: scans fill it, and operators can type or fix any value by hand — handy for unreadable labels. Every change syncs to the bound Livewire state and dispatches `qr-sequence-edited`. Pass `->editable(false)` to render locked read-only values instead. To flip modes live without a re-render wiping the running feed, dispatch `qr-sequence-editable` with `{ enabled }` on `window`; the component listens for it (its DOM is `wire:ignore`d so scans survive Livewire morphs).

#### Checked steps and auto-submit

Describe each step with `ScanStep` to check scans on the server and submit the form once the whole set is in:

```php
use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

QrScanSequence::make()
    ->statePath('receipt') // recommended: the key server checks use (or ->key('receipt'))
    ->steps([
        ScanStep::make('po')->label('Purchase order')->validateUsing(['regex:/^PO-\d+$/']),
        ScanStep::make('sku')->lookup(Product::class, 'sku'),
        ScanStep::make('lot')->validateUsing(['regex:/^L\d{3}$/'])->optional(),
        ScanStep::make('serial')->validateUsing(fn (string $code): ?string => str_starts_with($code, 'SN') ? null : 'Not a serial.'),
    ])
    ->validateAllUsing(fn (array $values): array => $values['po'] === $values['serial'] ? ['serial' => 'Serial repeats the PO.'] : [])
    ->onInvalidSequence(InvalidSequenceBehavior::JumpToStep) // or Restart
    ->onCompleted(fn (array $values) => /* ... */)
    ->autoSubmit()          // calls the Livewire submit() method
    ->resetAfterSubmit();   // or ->continuous() to start over after every set
```

- **One request per scan.** Each scan calls `handleStepScan` once. The server checks the step (rules, then lookup, then closures, the same pipeline as `QrScanner`), writes the value, and answers with the step to show next. A rejected scan keeps the operator on that step and shows the message.
- **Optional steps.** An optional step whose check fails gives way when the code fits the next step: the next step takes the code and the optional one stays empty. An optional step without checks accepts everything, so it never gives way.
- **Whole-set checks.** `validateAllUsing()` runs once every required step has a value. Rules are keyed by step (`['po' => 'different:serial']`); unkeyed rules apply to the last step. A closure returns `null` or `[]` to pass, a message (last step) or `[step => message]`. On failure the default (`jump_to_step`) keeps every value and asks for the failing step again; `Restart` clears the steps.
- **Completion.** `onCompleted($values)` runs and `ScanSequenceCompleted` fires, then `autoSubmit()` calls your submit method (`autoSubmit(method: 'save')` for another name). A `ValidationException` from it lands in the error bag and the values stay.
- **Defaults** live in config `sequence`: `on_invalid` `'jump_to_step'`, `auto_submit` `false`, `submit_method` `'submit'`, `reset_after_submit` `false`, `continuous` `false`.
- **Plain sequences don't change.** Without checks, `validateAllUsing()`, `onCompleted()`, `autoSubmit()` or `continuous()`, the browser writes state itself as before and makes no request. `fields([...])` is shorthand for steps without checks.
- **Browser events.** `qr-sequence-step` (now with `skipped` and `source`), `qr-sequence-completed` (now with `submitted`), plus `qr-sequence-rejected` `{ field, value, message, source }` and `qr-sequence-invalid` `{ field, message, values }`.

- **Normal submits check too.** `$this->form->getState()` re-runs every step check (on the step's state path) and `validateAllUsing()` (reported on the last step once every required step has a value). Empty steps pass; add `required` yourself if you need it.
- **Typed corrections.** In an editable sequence with server work, an edited step is checked like a scan with source `ScanInput::Manual` (`'manual'`). It never skips an optional step or moves the current step, and a rejected edit stays visible with its message.
- **Keys.** Server checks reach the component by its key. Give the sequence `statePath()` or `key()`; if it has neither, it gets an automatic key from its step keys (`qr-scan-sequence-po-sku`) and logs a warning in the local environment, so two identical sequences on one page need explicit keys.

#### Handheld scanners, sticky and repeat steps

A keyboard-wedge scanner fills the sequence without any focus: every burst goes to the current step, then the sequence moves on. If an operator focuses a step's input, the next burst goes to that step instead. While a server check is running, one burst waits and replays; any more are dropped (`qr-scan-dropped`).

```php
use Mmuqiitf\FilamentQrCode\Enums\RepeatComplete;

QrScanSequence::make()
    ->statePath('picking')
    ->steps([
        ScanStep::make('po')->sticky(),                      // kept for every item
        ScanStep::make('sku')->lookup(Product::class, 'sku'),
        ScanStep::make('serials')
            ->repeat(fn (array $values): int => Product::firstWhere('sku', $values['sku'] ?? null)?->serial_count ?? 1),
        ScanStep::make('extras')->repeat()->finishWith('DONE')->optional(), // open-ended
    ])
    ->continuous();
```

- **`sticky()`** keeps the step's value when the sequence resets (`resetAfterSubmit()`, `continuous()`, `onInvalidSequence(Restart)`). After a reset the sequence starts at the first empty step. To change a sticky value, select the step and scan again.
- **`repeat(n)`** collects `n` codes into a list (`['P1', 'P2', 'P3']`). Every code runs the step's checks, and a code already in the list is rejected unless the step has `allowDuplicates()`. The step shows `k / n`. `n` can be a closure that receives `$values`; it also runs when the page renders, before every step has a value, so handle missing keys.
- **When a repeat step is full**, config `sequence.repeat_complete` decides what happens: `'advance'` (default) moves to the next step; `'wait'` stays on the step and rejects further codes until the operator finishes it. Override it per step with `onRepeatComplete(RepeatComplete::Wait)`.
- **`repeat()` without a count** is open-ended: it keeps collecting until the operator finishes it.
- **Finishing a step**: scan the finish code (`finishWith('DONE')` on the step, else config `sequence.finish_code`, default `null` = no finish code) or press the step's **Done** button. A step needs at least one code (or all `n` in `wait` mode) before it can finish. The finish code is never stored.
- Sticky and repeat steps go through the server like checked steps (one `handleStepScan` request per scan). Sequences without them are unchanged.

### 4. Focus Handoff Between Fields

Move focus to the next field after each scan (a two-field handoff — distinct from sequential scanning above):

```php
QrScanner::make('batch_number')
    ->nextField('serial_number'),

QrScanner::make('serial_number')
    ->nextField('location_code'),

QrScanner::make('location_code'),
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
            ->fallbackToAnyInput(false) // never drop scans into search or filter boxes
            ->hardwareScanner(terminators: ['Enter', 'Tab'], minBarcodeLength: 2, minFastKeys: 3),
    ]);
}
```

Render `{{ $this->form }}` — it outputs nothing visible.

`fallbackToAnyInput(false)` is recommended for a till: a scan goes to the focused input or `scanInput`, and anything else raises `qr-scan-unrouted` instead of landing in an unrelated box. `minFastKeys: 3` keeps a cashier's fast typing from being taken for a scan.

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

## Server-side checks

```php
QrScanner::make('sku')
    ->validateUsing(['required', 'string', 'max:32'])
    ->lookup(Product::class, 'sku', modifyQueryUsing: fn (Builder $query) => $query->where('active', true))
    ->validateUsing(fn (string $code, ?Product $record): ?string => $record?->stock > 0 ? null : __('Out of stock'))
    ->cooldown(1500);
```

- Checks run on every live scan and again when the form is submitted, always in this order: rules, lookup, closures. The first failure stops the scan.
- A closure returns `null` or `true` to pass, a message to fail, or `false` to fail with the generic message.
- Name the closure parameter `$record` to receive the lookup result. A `Model`-typed parameter with another name receives the form's record instead.
- `cooldown()` sets how long the browser ignores a repeated code. Fields with checks default to `qr-code.scan.cooldown_ms` (1500 ms); fields without checks default to 0. Use `cooldown(0)` to turn it off. The browser enforces it: a repeat within the window fires `qr-scan-duplicate` and is not sent to the server.

### Reacting to scans

```php
QrScanner::make('sku')
    ->lookup(Product::class, 'sku')
    ->onScanned(function (ScanResult $result, Set $set): void {
        $set('price', $result->record->price);
    })
    ->onRejected(fn (ScanResult $result) => Notification::make()->danger()->title($result->message)->send())
    ->clearOnReject(); // default from qr-code.scan.clear_on_reject
```

- `onScanned` and `onRejected` run for live scans only, never when the form is submitted.
- Callbacks can ask for `ScanResult $result`, `Get $get`, `Set $set`, `string $code`, `$record` (named), `?ScanInput $source`, `$component` and `$livewire`.
- Accepted live scans fire `ScanAccepted` and `QrCodeScanned` (with `source` `camera`, `hardware` or `upload`); rejected ones fire `ScanRejected`. With `qr-code.audit.enabled`, accepted live scans are logged.
- If `onScanned` throws, the field keeps its previous value, the error is reported, and the scan is rejected with the generic message.
- Configuring `onScanned` or `onRejected` alone makes the field call the server on every scan and enables the default cooldown.

### In the browser

- A field with server checks sends each scan to the server once and waits for the answer. While it waits, the camera pauses and further scans are ignored.
- Accepted: the value stays, the success beep plays, and `qr-scan-accepted` and `qr-scanned` fire. Camera scans close the scanner.
- Rejected: the field clears (unless `clearOnReject(false)`), `qr-scan-rejected` fires once with the message, and the camera resumes so you can scan again.
- If the request fails, the scan is rejected with "The scan could not be checked. Please try again."
- The same code scanned again within the cooldown is ignored and fires `qr-scan-duplicate`.
- A page-level `QrHardwareScannerListener` hands bursts to a server-checked field, so wedge scans are checked too.

### Continuous mode

For POS line entry (scan, scan, scan) put the field in continuous mode. Every accepted code runs `onScanned()` once, and the field is ready for the next code straight away.

```php
use Mmuqiitf\FilamentQrCode\Enums\ContinuousDisplay;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;

QrScanner::make('scan')
    ->mode(ScanMode::Continuous)
    ->hardwareInput(scope: HardwareInputScope::Page) // catch wedge scans anywhere on the page
    ->lookup(Product::class, 'sku')
    ->onScanned(fn (Product $record, Get $get, Set $set) => $set('lines', addLine($get('lines'), $record)))
    ->continuousDisplay(ContinuousDisplay::Empty) // default; LastCode keeps the code in the field
    ->pauseOnReject(false)                         // default: show the error and keep scanning
    ->keepCameraOpen(false)                        // default: close the camera after each scan
    ->dehydrated(false);
```

- Each code is one server call (`handleScan`), even without rules or a lookup, because that is where `onScanned()` runs. Merging lines (same SKU twice = quantity 2) is your job in `onScanned()`.
- After an accepted scan the server clears the field (`continuousDisplay(ContinuousDisplay::Empty)`, the default) or keeps the code (`LastCode`). A `$set()` on the field's own path inside `onScanned()` is overwritten by the clear.
- A rejected scan shows its message under the field. By default scanning carries on. With `pauseOnReject()` the field stops taking scans until the operator presses **Resume scanning** (or Escape); `qr-scan-paused` and `qr-scan-resumed` fire.
- The camera closes after each accepted scan by default; `keepCameraOpen()` keeps it running for the next code.
- A repeat of the same code within 300 ms is ignored (`qr-code.scan.continuous.cooldown_ms`; `cooldown()` overrides). Raise it if a camera re-reads codes; lower it if cashiers scan the same item twice very quickly.
- A wedge scan that arrives while the field is still checking the previous one is queued (one slot) and checked next. Any further scan in that window is dropped and fires `qr-scan-dropped`.
- A continuous field is usually empty at submit, so don't make it `required()`. If you do, a warning is logged once in the local environment.

See the [POS recipe](recipes/pos.md) for a full page.

### Feedback

Fields with server checks give a different cue for each outcome:

| Outcome | Sound | Vibration | Flash |
|---|---|---|---|
| Accepted | one tone (`beepFrequency`, `beepDuration`) | `[100]` | green |
| Rejected or check failed | two low tones | `[80, 60, 80]` | red |
| Duplicate (within cooldown) | none | none | none |

```php
QrScanner::make('sku')
    ->lookup(Product::class, 'sku')
    ->beep(success: true, error: true)
    ->vibrate(success: [50], error: [200, 100, 200])
    ->flash(); // or flash(false)
```

Defaults live in `qr-code.feedback.success` and `qr-code.feedback.error`. `sound(false)` and `vibrate(false)` still turn everything off. Fields without checks keep the single success beep. The ring lasts about 0.9 s on the field; while the scanner modal is open it flashes the modal panel instead, and the field ring plays once the modal closes.
