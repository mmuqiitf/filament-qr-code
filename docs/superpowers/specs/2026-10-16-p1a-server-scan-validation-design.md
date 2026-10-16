# P1a: Server-side scan validation (design spec)

- **Date:** 2026-10-16
- **Status:** Approved by owner 2026-10-16 (open questions resolved, section 9)
- **Roadmap items:** P1.1–P1.5 in the review plan (section F)
- **Scope:** Sub-project 1 of 3 of P1. This spec covers only `QrScanner`.

## 1. Problem

Today a live scan never reaches the server in a way that can return a result.
`QrScanner::handleScanResult()` (in `resources/js/qr-scanner.js`) writes state with
`$wire.set(path, code)`, plays the success beep through `emitScanFeedback()`, and dispatches
the `qr-scanned` window event. Server-side reaction is limited to `afterStateUpdated` hooks
(`rejectWhen()`, `normalizeUsing()`). These can clear the value, but they can't look up a record,
return a result to the browser, or drive error feedback. `onScan()`/`triggerOnScan()` and
`scanFormat()` are programmatic-only, so `QrCodeScanned` is never fired for live scans
(gap A.3-1). The camera can re-fire the same code at 25 fps (gap A.3-5), and feedback only
covers success (gap A.3-6).

## 2. Goals and non-goals

**Goals**

1. Validate each live scan on the server (rules, model lookup, closures) and return a structured result.
2. Let apps react to accepted and rejected scans with `Get`/`Set` access to form state.
3. Fire `QrCodeScanned` (plus new `ScanAccepted`/`ScanRejected`) for live scans.
4. Client-side duplicate suppression (cooldown) and a paused camera while a check is in flight.
5. Distinct success and error feedback (sound, vibration pattern, flash).
6. No behaviour change for fields that configure none of the new server checks.

**Out of scope (later sub-projects)**

- **Sub-project 2: hardware routing and continuous mode** (P1.6 input router and `hardwareInput(scope:)`, P1.7 `mode(ScanMode::Continuous)`). This spec adds no `ScanMode` enum.
- **Sub-project 3: sequences** (P1.8–P1.10: `ScanStep`, per-step validation, `validateAllUsing`, `onCompleted`, `autoSubmit`). `QrScanSequence` and `QrCollector` are not changed here.
- Generators, layouts and docs (P2/P3).

**Owner decisions recorded for later sub-projects** (not implemented here):

- Merging cart lines (same SKU scanned twice) is the app's job, done in `onScanned()`. The package won't add a merge helper.
- Sequence `autoSubmit` is configurable: submit after the last field, or not.
- Hardware prefix, suffix, terminator and timing are configurable in config, with per-field overrides.

## 3. Approach

One Livewire request per scan. The browser calls a server method on the `QrScanner` component,
which runs the pipeline and returns a `ScanResult` array. That one response drives state,
events and feedback.

### 3.1 Rejected alternatives

| Alternative | Why rejected |
|---|---|
| Extend `afterStateUpdated` (how `rejectWhen()` works today) | A state hook returns nothing to the caller, so the browser can't tell pass from fail, can't get a message or a record, and can't choose error feedback. Results would need a second round trip by browser event. |
| Separate HTTP endpoint (controller route) | It has no access to the Livewire form state, so `Get`/`Set` and record-aware closures are impossible. It also needs its own auth, CSRF and authorization of the field and model, which duplicates what Livewire already gives us. |

### 3.2 Filament v5 call path (spike result)

The spike read `vendor/filament` (v5). Filament already ships a documented way for a browser
to call a method on a schema component:

- `Filament\Schemas\Concerns\InteractsWithSchemas::callSchemaComponentMethod(string $componentKey, string $method, array $arguments = [])` is a public Livewire method on every Filament page or Livewire component that has schemas.
- It resolves the component with `getSchemaComponent($componentKey)`, so only components that are part of a mounted schema can be reached. An unknown key returns `null`.
- It only dispatches to methods marked with `#[Filament\Support\Components\Attributes\ExposedLivewireMethod]`. Others return `null`.
- A method also marked `#[Livewire\Attributes\Renderless]` skips re-rendering.
- Core fields use this path: `FileUpload` (`deleteUploadedFile`, `getUploadedFiles`), `RichEditor`, `Select`, `MarkdownEditor`, and `HasFileAttachments`.

**Decision:** use `callSchemaComponentMethod` with `#[ExposedLivewireMethod]`. The browser calls:

```js
const result = await this.$wire.callSchemaComponentMethod(componentKey, 'handleScan', {
    code,
    source,
});
```

`componentKey` is rendered into the Alpine payload from `$getKey()`, in the same way `FileUpload`
renders `Js::from($key)`. Arguments are passed by name, matching the `{ fileKey }` style in
`FileUpload`. The hidden-`Action` fallback mentioned in plan B.4 is not needed.

The owner's sketch was `$wire.handleScan(statePath, code, source)`. The decision keeps that
contract (one call per scan, one result back) but addresses the field by component key instead
of state path. This also gives the "only registered scanner fields" guard for free: a key that
isn't a mounted component, or a component without an exposed `handleScan`, returns `null`, and
the client treats `null` as a rejected scan (sections 4.5 and 5.2). The state path is still read on the
server from `$this->getStatePath()`, so the client can't pick which path is written.

`handleScan` is **not** `#[Renderless]`. `onScanned()` callbacks may `$set()` other fields,
so the schema must re-render.

## 4. PHP design

Code examples follow `.agents/skills/spatie-laravel-php`: typed properties and return types,
constructor promotion, enums with TitleCase cases, `snake_case` config keys, early returns,
and no docblocks that repeat type hints.

### 4.1 New types

```php
namespace Mmuqiitf\FilamentQrCode\Enums;

enum ScanInput: string
{
    case Camera = 'camera';
    case Hardware = 'hardware';
    case Upload = 'upload';
}
```

```php
namespace Mmuqiitf\FilamentQrCode\Support;

use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;

final readonly class ScanResult
{
    public function __construct(
        public bool $passes,
        public string $code,
        public ?ScanInput $source,
        public ?Model $record = null,
        public ?string $message = null,
    ) {}

    public static function accept(string $code, ?ScanInput $source, ?Model $record = null): self
    {
        return new self(passes: true, code: $code, source: $source, record: $record);
    }

    public static function reject(string $code, ?ScanInput $source, string $message): self
    {
        return new self(passes: false, code: $code, source: $source, message: $message);
    }

    /** @return array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed} */
    public function toClient(): array
    {
        return [
            'passes' => $this->passes,
            'code' => $this->code,
            'source' => $this->source?->value,
            'message' => $this->message,
            'record_key' => $this->record?->getKey(),
        ];
    }
}
```

Only the record key goes to the browser; the full model stays on the server.
`message` is always already translated. `source` is `null` only when the checks run at form submit (section 4.4), where no scan input exists.

Events (`src/Events`), plain classes like the existing `QrCodeScanned`:

```php
final class ScanAccepted
{
    public function __construct(
        public ScanResult $result,
        public string $field,
    ) {}
}

final class ScanRejected
{
    public function __construct(
        public ScanResult $result,
        public string $field,
    ) {}
}
```

`QrCodeScanned` keeps its signature. Live accepted scans fire it with
`source: $result->source->value` (`camera`, `hardware` or `upload`), `field: $this->getName()`,
and `context: ['record_key' => ...]`. `triggerOnScan()` keeps firing it with `source: 'scanner-field'`.

### 4.2 `InteractsWithScans` concern

A new trait in `src/Concerns/InteractsWithScans.php`, used by `QrScanner`. It owns the
configuration API and `handleScan()`.

```php
QrScanner::make('sku')
    ->validateUsing(['required', 'string', 'max:32'])
    ->validateUsing(new ValidSkuChecksum())
    ->lookup(Product::class, 'sku', modifyQueryUsing: fn (Builder $query) => $query->where('active', true))
    ->validateUsing(fn (string $code, ?Product $record): ?string => $record?->stock > 0 ? null : __('Out of stock'))
    ->onScanned(function (ScanResult $result, Set $set, Get $get): void {
        $set('price', $result->record->price);
    })
    ->onRejected(fn (ScanResult $result) => Notification::make()->danger()->title($result->message)->send())
    ->clearOnReject()
    ->cooldown(1500);
```

| Method | Signature | Notes |
|---|---|---|
| `validateUsing()` | `validateUsing(array\|ValidationRule\|Closure $rules): static` | May be called several times; entries are appended in call order. An array is a Laravel rule list (strings and rule objects). A `ValidationRule` object is wrapped in a one-item list. A `Closure` is a scan check (below). |
| `lookup()` | `lookup(string $model, string $column = 'code', ?Closure $modifyQueryUsing = null): static` | `$model` is a `class-string<Model>`. Calling it twice replaces the earlier lookup. |
| `onScanned()` | `onScanned(?Closure $callback): static` | Runs after state is set on a pass. |
| `onRejected()` | `onRejected(?Closure $callback): static` | Runs on a fail. |
| `clearOnReject()` | `clearOnReject(bool\|Closure $condition = true): static` | Default from `qr-code.scan.clear_on_reject`. |
| `cooldown()` | `cooldown(int\|Closure $milliseconds): static` | Default: `qr-code.scan.cooldown_ms` (1500) when `hasServerScanChecks()` is true, otherwise `0`. An explicit value always wins. `0` disables dedupe. Negative values are clamped to `0`. |
| `runScanChecks()` | `runScanChecks(string $code, ?ScanInput $source): ScanResult` | Stages 3–6 of the pipeline only: no state writes, callbacks or events. Shared by `handleScan()` and the submit rule. |
| `handleScan()` | `#[ExposedLivewireMethod] handleScan(string $code, string $source): array` | Calls `runScanChecks()`, applies side effects, returns `ScanResult::toClient()`. |
| `hasServerScanChecks()` | `hasServerScanChecks(): bool` | `true` when at least one `validateUsing()` entry, a `lookup()`, an `onScanned()` or an `onRejected()` callback is configured. |

**Closure checks.** A closure passed to `validateUsing()` is evaluated with Filament's
`evaluate()`, so it can ask for `code`, `record` (the lookup result, or `null`), `source`,
`get`, `set`, `component` and `livewire`. It returns `null` (or `true`) to pass, or a
string message (or `false`, which uses the generic `filament-qr-code::ui.scan_rejected` message) to fail.
Anything else is treated as a pass.

**Callback injection.** `onScanned()` and `onRejected()` closures are also evaluated with
`evaluate()`. Available named and typed parameters: `ScanResult $result`,
`Filament\Schemas\Components\Utilities\Get $get`, `Filament\Schemas\Components\Utilities\Set $set`,
`string $code`, `?Model $record`, `ScanInput $source`, `$component`, `$livewire`.
Typed parameters resolve by type, so `fn (ScanResult $r, Set $s, Get $g)` works.

### 4.3 Server pipeline

`handleScan(string $code, string $source)`:

1. **Parse input.** `ScanInput::tryFrom($source)`; an unknown source becomes `ScanInput::Camera`. The code is trimmed. An empty code is rejected with `filament-qr-code::ui.scan_empty`.
2. **Guard.** If `hasServerScanChecks()` is false, the method still works (it accepts and sets state) but the client never calls it in that case (section 5.4). If the field is disabled or read-only, reject with `filament-qr-code::ui.scan_rejected`.
3. **Rules.** All array and rule-object entries are merged into one list and run with `Validator::make(['code' => $code], ['code' => $rules], attributes: ['code' => $this->getLabel()])`. The first error message is the result message, translated by Laravel's validator.
4. **Lookup.** If configured: `$model::query()`, then `modifyQueryUsing` (evaluated with `query`), then `->where($column, $code)->first()`. A miss rejects with `filament-qr-code::ui.record_not_found` (`:code` replaced).
5. **Closures.** Closure checks run in the order they were added, receiving the lookup `record`.
6. The first failing stage stops the pipeline. Rule arrays and rule objects always run before the lookup, and closures always run after it, whatever order the `validateUsing()` calls were made in. This lets closures use `$record`.
7. **On pass:** `$this->state($code)` (and `callAfterStateUpdated()`, so existing `normalizeUsing()`/`rejectWhen()` keep working), then `onScanned`, then `event(new ScanAccepted(...))` and `event(new QrCodeScanned(...))`.
8. **On fail:** state is not written. If `clearOnReject` is on, `$this->state(null)`. Then `onRejected`, then `event(new ScanRejected(...))`. The server does not dispatch `qr-scan-rejected` here; the client dispatches it once from the returned result (section 5.2), so listeners get one event per rejection.
9. Return `toClient()`.

Stages 3–6 are implemented once, in `runScanChecks()`. Stages 7–9 live in `handleScan()`.

### 4.4 Submit-time checks

Submit validation re-runs the whole check pipeline, so a bypassed or stale browser can't save a
code that a live scan would reject. When `hasServerScanChecks()` has checks to run (any
`validateUsing()` entry or a `lookup()`), `InteractsWithScans` registers one extra field rule in
`setUp()`:

```php
$this->rule(fn (QrScanner $component): ScanChecksRule => new ScanChecksRule($component));
```

```php
namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

final readonly class ScanChecksRule implements ValidationRule
{
    public function __construct(
        private QrScanner $component,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $result = $this->component->runScanChecks((string) $value, source: null);

        if ($result->passes) {
            return;
        }

        $fail($result->message);
    }
}
```

- The rule runs rules, lookup and closures in the same order and with the same messages as a live scan. Rule arrays and rule objects are **not** also added through `$this->rules()`, so a failing rule reports one error, not two.
- Blank values pass; `required()` stays the way to demand a value.
- `onScanned`, `onRejected`, events and state writes never run at submit.
- Closure checks receive `source = null` at submit and must tolerate it.
- An exception during submit checks is reported and fails with `scan_failed`, as for live scans.
- Cost: the lookup query runs once per scan and once more per submit. This is accepted.

### 4.5 Error handling

- Any `Throwable` thrown inside stages 3–8 is caught, passed to `report($e)`, and turned into `ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_failed'))`. `ValidationException` is not reported, only converted.
- A lookup miss gives `record_not_found` (not `scan_failed`).
- An unregistered component key or a missing method returns `null` from Filament; the client shows the `scan_failed` message (passed in the Alpine payload) as a rejection.
- A network or Livewire error rejects the promise; the client handles it the same way and logs it with `console.error`, not silently.

New translation keys in `resources/lang/{en,id}/ui.php`: `scan_empty`, `scan_rejected`,
`scan_failed`, `record_not_found` (`No record found for ":code".`).

## 5. JavaScript design

### 5.1 Payload additions

`InteractsWithScans::getScanChecksScanPayload()` contributes these keys. It is registered in the `ScanOptions::Contributors` map (`InteractsWithScans::class => 'getScanChecksScanPayload'`), like the other scan traits, so `getComponentScanPayload()` stays free for surface-specific options:

```php
[
    'componentKey' => $this->getKey(),
    'serverCheck' => $this->hasServerScanChecks(),
    'clearOnReject' => $this->shouldClearOnReject(),
    'cooldownMs' => $this->getCooldownMs(),
    'scanFailedMessage' => __('filament-qr-code::ui.scan_failed'),
]
```

`HasFeedback` adds `successFeedback` and `errorFeedback` (section 5.3).

### 5.2 Client flow (`qr-scanner.js` plus a new `scan-pipeline.js`)

`handleScanResult(scannedText, source = 'camera')` becomes:

1. Sanitize (unchanged). Upload calls pass `'upload'`, hardware routing passes `'hardware'`.
2. **Dedupe.** If `cooldownMs > 0` and the code equals the last accepted-or-submitted code within `cooldownMs`, drop it and dispatch the window event `qr-scan-duplicate` with `{ value, field, source }`. No sound.
3. **No server check** (`serverCheck === false`): today's behaviour exactly: `$wire.set(path, code)`, success feedback, `qr-scanned`, `advanceFocus(nextField)`.
4. **Server check:** if a check is already in flight, ignore the scan. Otherwise mark in flight, pause the camera (`html5QrCode.pause(true)` when scanning), and await `callSchemaComponentMethod(componentKey, 'handleScan', { code, source })`.
5. Result `null` or a thrown error → treat as `{ passes: false, message: scanFailedMessage }`.
6. **Pass:** set `this.value = code`, success feedback, dispatch `qr-scan-accepted` and the existing `qr-scanned` (adding `source` and `recordKey`), then `advanceFocus(nextField)`.
7. **Fail:** if `clearOnReject`, `this.value = ''`; error feedback; dispatch `qr-scan-rejected` with `{ value, field, source, message }`.
8. Clear in flight and resume the camera in `finally`.

The pure parts (dedupe window, result normalisation, outcome → feedback mapping) live in
`resources/js/scan-pipeline.js` as exported functions so they can be tested without Alpine.

### 5.3 Feedback

`audio-feedback.js` `QrFeedback::trigger()` gains an `outcome` (`'success' | 'error'`). Defaults:

| Outcome | Sound | Vibration | Flash |
|---|---|---|---|
| success | one tone at `beep_frequency` for `beep_duration_ms` | `[100]` | `success` |
| error | two low tones (220 Hz) | `[80, 60, 80]` | `danger` |

Flash adds a `fi-qr-flash-success` or `fi-qr-flash-danger` class to the field wrapper for 300 ms.
Existing `sound()`, `vibrate()`, `beepFrequency()`, `beepDuration()` and `vibrateDuration()` keep
working and configure the success outcome. New per-field overrides on `HasFeedback`:
`successFeedback(?bool $sound = null, ?array $vibrate = null, ?bool $flash = null)` and
`errorFeedback(...)` with the same parameters.

### 5.4 Backward compatibility

- Fields with no `validateUsing()`, `lookup()`, `onScanned()` or `onRejected()` make no `handleScan` call, get no submit rule, and default to `cooldown 0`; state, events and the success beep are unchanged. An app can opt in to dedupe with `cooldown(1500)`.
- `scanRules()` keeps adding plain field rules (submit only), as today.
- `rejectWhen()`, `normalizeUsing()`, `scanRules()`, `onScan()`/`triggerOnScan()` and `scanFormat()` are unchanged.
- Config keys are only added.

## 6. Config

Added to `config/qr-code.php` (all overridable per field):

```php
'scan' => [
    'clear_on_reject' => true,
    'cooldown_ms' => 1500, // used only by fields with server checks; others default to 0
],

'feedback' => [
    // existing keys stay: sound, beep_frequency, beep_duration_ms, vibrate, vibrate_duration_ms
    'success' => [
        'sound' => true,
        'vibrate' => [100],
        'flash' => true,
    ],
    'error' => [
        'sound' => true,
        'vibrate' => [80, 60, 80],
        'flash' => true,
    ],
],
```

Values are read through `Support\ConfigValue`, as the existing `HasFeedback` getters do.

## 7. Testing

TDD: each PR starts with a failing test.

**Pest `tests/Feature/HandleScanTest.php`** (Livewire test component with a form containing `QrScanner`, calling `callSchemaComponentMethod`):

- rule array failure returns `passes=false` with the translated validator message (en and id)
- rule object and closure failures; closure `false` gives `scan_rejected`
- lookup sets `record_key`; a miss rejects with `record_not_found`; `modifyQueryUsing` is applied
- order: rules before lookup before closures; first failure stops (later stages are not called)
- pass sets state and runs `onScanned` with working `Get`/`Set`; fail runs `onRejected`, clears state when `clearOnReject`, keeps it when off
- events: `ScanAccepted` + `QrCodeScanned` (source `hardware`) on pass; `ScanRejected` on fail
- a thrown exception becomes `scan_failed` and is reported (`Exceptions::fake()`)
- unknown component key and non-scanner component return `null`
- `hasServerScanChecks()` is false, `serverCheck` is `false` and `cooldownMs` is `0` in the payload when nothing is configured; `cooldownMs` is `1500` once a check is added
- submit: a code that fails a rule, misses the lookup or fails a closure blocks `getState()` with the same message; a passing code submits; callbacks and events don't fire at submit; a blank value with no `required()` passes
- `ScanPayloadCharacterizationTest` is updated for the added keys only

**Vitest** (`resources/js/scan-pipeline.test.js`, `qr-scanner.test.js`, `audio-feedback` tests):

- same code within cooldown is dropped and emits `qr-scan-duplicate`; `cooldown 0` never drops
- unconfigured field makes no `callSchemaComponentMethod` call and exactly one `$wire.set`
- reject clears the value when `clearOnReject`, keeps it otherwise
- success and error outcomes play the matching tone, vibration pattern and flash class
- a second scan while in flight is ignored; camera paused then resumed, also after an error
- a `null` result or rejected promise shows `scanFailedMessage`

**Livewire E2E** (extend `tests/Feature/LivewireFormE2ETest.php`): a form with a `lookup()` scanner
accepts a known SKU and `$set`s a sibling field, and rejects an unknown SKU with the message.

## 8. PR breakdown

| PR | Content | First failing test |
|---|---|---|
| 1 | `ScanInput`, `ScanResult`, `InteractsWithScans::handleScan()` via `#[ExposedLivewireMethod]` (accept-only), payload `componentKey`/`serverCheck` | "handleScan via callSchemaComponentMethod returns passes=true and sets state" |
| 2 | `validateUsing()` (arrays, rule objects, closures), `lookup()`, `runScanChecks()` order, error handling, translations, `ScanChecksRule` for submit | "handleScan returns passes=false with translated message when rule fails" |
| 3 | `onScanned()`/`onRejected()` with `Get`/`Set`, `clearOnReject()` (server side), `ScanAccepted`/`ScanRejected`/`QrCodeScanned` | "accepted scan dispatches ScanAccepted and QrCodeScanned with source=hardware" |
| 4 | `scan-pipeline.js`, dedupe/`cooldown()` (default 0 without checks), in-flight pause, client clear on reject, hardware stopgap, config `scan.*` | "same code within 1500 ms is dropped and emits qr-scan-duplicate" |
| 5 | Outcome feedback (sound, vibration pattern, flash), config `feedback.success/error`, `successFeedback()`/`errorFeedback()` | "rejected result plays error tone and adds danger flash class" |

## 9. Resolved questions

Resolved by the owner on 2026-10-16:

1. **Cooldown on fields without checks:** defaults to `0`, so apps that configure no checks see no change. Fields with checks default to `scan.cooldown_ms` (1500). See sections 4.2 and 5.4.
2. **Hardware path:** the PR 4 stopgap is accepted. `qr-hardware-scanner.js` (around line 355) writes `this.$wire.set(livewirePath, scannedValue)` directly. In PR 4, when the target is a `QrScanner` field with `serverCheck`, the hardware scanner calls that field's `handleScanResult(code, 'hardware')` instead; otherwise it keeps writing directly. The full router rewrite stays in sub-project 2.
3. **Checks on submit:** lookup and closure checks re-run on submit, together with rules, through `ScanChecksRule` (section 4.4).
