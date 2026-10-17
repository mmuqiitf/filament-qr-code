# P1c: Scan sequences with steps, validation and wedge-driven flows (design spec)

- **Date:** 2026-10-17
- **Status:** Draft for owner review (binding decisions in section 2.3, open questions in section 12)
- **Roadmap items:** P1.8, P1.9 and P1.10 in the review plan (section F)
- **Scope:** Sub-project 3 of 3 of P1. Builds on P1a (`ScanResult`, `handleScan`, checks, outcome feedback) and P1b (input router, `hardwareInput`, continuous mode). Nothing is released until all of P1 is done.

## 1. Problem

`QrScanSequence` today is a camera/wedge checklist with no server involvement:

- Steps are `{key, label}` pairs from `fields([...])` (`getScanFields()`). There are no per-step rules, lookups or optional steps.
- A scan is written straight into Livewire with `$wire.set()` and the sequence moves on (`qr-sequence.js` `processScan()`). Nothing checks the value, so a wrong SKU in step 2 is accepted and the operator only notices at submit.
- Completion is a window event (`qr-sequence-completed`). There is no whole-set check, no server callback, no auto-submit and no reset for the next item.
- `onStepScanned()`/`scanFormat()` are programmatic-only.
- Wedge input reaches the sequence through the P1b router as a page target, but there is no `sticky()` (keep a value such as the purchase order across items) and no `repeat(n)` (scan n components of a BOM into one step).

## 2. Goals and non-goals

**Goals**

1. `ScanStep` value object and `QrScanSequence::steps([...])`, with `fields([...])` kept as an adapter that produces exactly the same payload (P1.8).
2. Per-step server checks with the P1a pipeline: Laravel rules, a per-step `lookup()` and closures, one Livewire call per scan (P1.8/P1.9).
3. Optional steps, auto-skipped when the scanned code fits the next step (decision 1).
4. `validateAllUsing()` on the complete set, `onCompleted()`, `autoSubmit()`, `resetAfterSubmit()` and sequence `continuous()` (P1.9), with a configurable failure behaviour (decision 2).
5. Wedge-driven sequences with `sticky()` and `repeat(n)` (P1.10), with a configurable "repeat complete" behaviour (decision 3).
6. Existing sequences (plain `fields()`, no checks) behave exactly as today.

**Out of scope**

- `manual()` steps (typed, not scanned) and step `default()` values: the existing `editable()` covers corrections; revisit after P1.
- Duplicate scopes across sessions (`rejectDuplicates(scope:, ttl:)`): P2 with the warehouse recipe (P2.4).
- Re-running step checks on a normal (non-auto) form submit: the sequence is a layout component, not a field, so it has no validation hook today. Apps can call `isSequenceComplete()`; see open question 3.
- Cart/line merging: the app's job in `onCompleted()`.
- Per-step `inputs([...])` restrictions (B.3 sketch): the P1b router already decides which input reaches the sequence.

### 2.1 Requirements traced

- Workflow (b) "scan several, validate each, then all, then submit": goals 1–4.
- Requirement 2 (cashier/warehouse wedge plus multi-field) and the manufacturing BOM loop: goal 5.

### 2.2 Conventions

`.agents/skills/spatie-laravel-php` (final value objects with fluent setters, TitleCase enum cases, `snake_case` config keys, config default + per-component/per-step fluent override via `ConfigValue`, early returns) and `.agents/skills/spatie-javascript`. Browser events keep the `qr-sequence-*` names and only gain fields.

### 2.3 Owner decisions (binding, 2026-10-17)

1. In scanner-driven sequences, an optional step is **auto-skipped when the scanned code fits the next step**: the code fails (or doesn't match) the optional step but passes the next step's checks.
2. When `validateAllUsing` fails, behaviour is configurable; **default keeps the values and jumps to the failing step**.
3. When a `repeat(n)` step has all its scans, behaviour is configurable; **default auto-advances to the next step**.

Carried over from P1a/P1b: `autoSubmit` is a toggle (config + per sequence); app defaults live in `config/qr-code.php` with fluent overrides; lookups are per step with no config default model; checks are rules, a lookup and closures; one Livewire call per scan; merging is the app's job; existing users unaffected.

## 3. Approach

The sequence becomes a server-checked component in the same way `QrScanner` did in P1a: the browser calls one exposed method per scan, `handleStepScan`, through `callSchemaComponentMethod`, and the returned result drives the step pointer, state and feedback. The check pipeline from P1a is extracted into a reusable `ScanCheckPipeline` so `QrScanner` and `ScanStep` share it.

### 3.1 Rejected alternatives

| Alternative | Why rejected |
|---|---|
| Nest real `QrScanner` fields as steps | Each would own its own router target, camera and state path; one sequence would become N components fighting for bursts. Breaks the existing single-viewfinder UI and payload. |
| Validate only on completion | Fails workflow (b) "validate each": the operator would scan four codes before learning step 2 was wrong. |
| Client-side step routing for optional skips | The "fits the next step" test needs the next step's server checks (lookup); doing it in the browser means two round trips per scan. |

## 4. PHP design

### 4.1 `ScanStep`

```php
namespace Mmuqiitf\FilamentQrCode\Support;

final class ScanStep
{
    public static function make(string $key): self;

    public function label(string|Closure|null $label): self;
    public function validateUsing(array|ValidationRule|Closure $rules): self; // same contract as QrScanner
    public function lookup(string $model, string $column = 'code', ?Closure $modifyQueryUsing = null): self;
    public function optional(bool|Closure $condition = true): self;

    // PR B (P1.10)
    public function sticky(bool|Closure $condition = true): self;
    public function repeat(int|Closure $times): self;
    public function onRepeatComplete(RepeatComplete $behaviour): self;

    public function getKey(): string;
    public function getLabel(): string;          // default: ucwords of the key, as today
    public function isOptional(): bool;
    public function hasChecks(): bool;
    public function toField(): array;            // ['key' => ..., 'label' => ...]
}
```

- Step keys are the state keys (snake_case by convention, not enforced, to stay compatible with existing keys).
- Closures in a step are evaluated through the owning sequence (`evaluate()`), so they get the usual injections plus `$step`, `$code`, `$record`, `$source`, `$values` (values captured so far), `Get`/`Set`.
- Checks run rules → lookup → closures; first failure stops (P1a order).

### 4.2 `QrScanSequence` additions

```php
QrScanSequence::make()
    ->statePath('receipt')
    ->steps([
        ScanStep::make('purchase_order')->lookup(PurchaseOrder::class, 'number'),
        ScanStep::make('sku')->lookup(Product::class, 'sku')->validateUsing(new SkuBelongsToPurchaseOrder()),
        ScanStep::make('lot')->validateUsing(['regex:/^L\d{6}$/'])->optional(),
    ])
    ->validateAllUsing(new ReceiptIsConsistent())
    ->onInvalidSequence(InvalidSequenceBehavior::JumpToStep)  // default from config
    ->onCompleted(fn (array $values, ReceiveGoods $livewire) => $livewire->receive($values))
    ->autoSubmit()                    // default from config (false)
    ->resetAfterSubmit()              // default from config (false)
    ->continuous();                   // default from config (false)
```

- `steps(array|Closure $steps)`: list of `ScanStep`. `fields(...)` keeps its signature and is converted with `ScanStep::fromField()`; `getScanFields()` keeps returning `[{key, label}]` from the steps, so the existing `fields` payload key is identical (acceptance test P1.8).
- `getScanSteps(): array<int, ScanStep>`.
- `validateAllUsing(array|ValidationRule|Closure $rules)`:
  - array keyed by step (`['sku' => [new SkuOnPo()]]`) runs the Laravel validator over the captured values; the failing key is the failing step (rules can implement `DataAwareRule` to see the whole set);
  - an unkeyed `ValidationRule` or list of rules validates the whole values array under the attribute `values`; failing step = the last step;
  - a closure receives `$values` and returns `null`/`true` (pass), `false` (generic message, last step), a string (message, last step) or `[stepKey => message]` (that step).
- `onInvalidSequence(InvalidSequenceBehavior|Closure)`: `JumpToStep` (keep values, move the pointer to the failing step, clear only that step) or `Restart` (clear all non-sticky values, back to the first step). Config `sequence.on_invalid`.
- `onCompleted(?Closure)`: runs once per valid complete set with `$values`, `Get`, `Set`, `$livewire`, `$component`.
- `autoSubmit(bool|Closure $condition = true, string $method = 'submit')`: after `onCompleted`, calls `$livewire->{$method}()` in the same request. Config `sequence.auto_submit` (false) and `sequence.submit_method` ('submit'). A `ValidationException` from the submit leaves the sequence complete and returns `submitted: false`; the form shows its own errors.
- `resetAfterSubmit(bool|Closure $condition = true)`: clear the sequence state after a successful submit (or after `onCompleted` when `autoSubmit` is off). Config `sequence.reset_after_submit` (false).
- `continuous(bool|Closure $condition = true)`: after completion (and reset), restart at the first step without stopping the camera. Config `sequence.continuous` (false). Implies reset.
- `onStepScanned()` and `scanFormat()` stay programmatic-only (documented).

### 4.3 `handleStepScan`

```php
#[ExposedLivewireMethod]
public function handleStepScan(string $step, string $code, string $source): array
```

Server flow for one scan (one Livewire request):

1. Resolve `$step` to a `ScanStep` (unknown → reject `scan_rejected`). Normalize the code (`normalizeStepUsing`).
2. Run the step's pipeline. On failure and the step is optional: run the next step's pipeline; on success record the code on the next step and mark the optional step skipped (**decision 1**). Otherwise reject.
3. On accept: write the value into the sequence state (own `statePath` and legacy `statePathPrefix` path, as the browser does today), fire `ScanAccepted` + `QrCodeScanned` (field `"{$step}"`), compute the next unfilled step.
4. When no required step is left: run `validateAllUsing` → on failure apply `onInvalidSequence` and return `invalid` with the failing step; on success run `onCompleted`, fire `ScanSequenceCompleted`, `autoSubmit`, then reset/continuous.
5. Return the result array (section 4.4).

The client still calls `$wire.set()` directly (today's path) when the sequence has **no** server work: no step checks, no `validateAllUsing`, no `onCompleted`, no `autoSubmit`. That keeps existing sequences unchanged and request-free.

### 4.4 Result shape

```php
[
    'passes' => bool,
    'code' => string,
    'step' => ?string,          // step that received the code (may differ from the request after a skip)
    'skipped' => list<string>,  // optional steps skipped by this scan
    'message' => ?string,
    'record_key' => mixed,
    'next_step' => ?string,     // where the pointer goes; null when complete
    'completed' => bool,
    'invalid_step' => ?string,  // validateAll failure
    'submitted' => ?bool,       // null when autoSubmit is off
    'reset' => bool,            // state was cleared (resetAfterSubmit / continuous / Restart)
    'values' => array<string, string>, // captured values after this scan
]
```

Built by a small `SequenceScanResult` value object (`toClient()`), mirroring `ScanResult`.

### 4.5 Events

- New `ScanSequenceCompleted(array $values, ?string $statePath)` (tense-named, plan B.4).
- Per step: `ScanAccepted`/`ScanRejected` with `field = step key`; `QrCodeScanned` for accepted steps.

### 4.6 New enums

```php
enum InvalidSequenceBehavior: string { case JumpToStep = 'jump_to_step'; case Restart = 'restart'; }
enum RepeatComplete: string { case Advance = 'advance'; case Wait = 'wait'; }        // PR B
```

## 5. JS design (`qr-sequence.js`)

- Payload adds `serverCheck`, `componentKey`, `steps` (`[{ key, optional, sticky, repeat }]`, PR B fields default `false`/`1`), `cooldownMs`, `continuous`, `scanFailedMessage`, `successFeedback`/`errorFeedback`.
- `processScan(text, source)`: sanitize → dedupe (P1a `createScanDeduper`, cooldown default `scan.cooldown_ms` when `serverCheck`, else 0) → no server work: today's path → server work: in-flight guard, pause camera, `requestSequenceScan()` (new helper in `scan-pipeline.js`), then:
  - `passes`: set `results` from `values`, move `currentFieldIndex` to `next_step`, success feedback, `qr-sequence-step` (adds `skipped`, `source`);
  - rejected: stay, error feedback, `rejectionMessage` under the active step, `qr-sequence-rejected` `{ field, value, message }`;
  - `completed`: `qr-sequence-completed` (adds `submitted`); stop camera unless `continuous`; when `reset`, clear `results` and go to the first step;
  - `invalid_step`: error feedback, `qr-sequence-invalid` `{ field, message }`, pointer to that step.
- Router target `accept()` is false while checking (P1b fall-through).

## 6. Config

```php
'sequence' => [
    'on_invalid' => 'jump_to_step',  // 'jump_to_step' | 'restart'   (decision 2)
    'auto_submit' => false,
    'submit_method' => 'submit',
    'reset_after_submit' => false,
    'continuous' => false,
    'repeat_complete' => 'advance',  // 'advance' | 'wait'            (decision 3, PR B)
],
```

## 7. P1.10: wedge-driven sequences, `sticky()`, `repeat(n)` (PR B)

- The sequence is already a page-scope router target (P1b). PR B makes the active step explicit to the router (`ownsFocus` for the step inputs, so a focused step input takes the burst for that step) and adds `qr-sequence-step` `source: 'hardware'`. Acceptance: "three bursts fill steps po, sku, lot in order; sticky po survives reset".
- `sticky()`: the value survives `resetAfterSubmit`/`continuous`/`Restart`; after a reset the pointer starts at the first non-sticky empty step. Clearing a sticky step: click it and rescan, or the "Clear" control.
- `repeat(int|Closure $times)`: the step collects `n` codes into a list (`values[step]` is `list<string>`); each scan runs the step checks; the step label shows `k / n`. A `Closure` can read other steps (`$values`, `Get`) such as the BOM count of the work order.
- When `k === n`: `RepeatComplete::Advance` (default, **decision 3**) moves to the next step; `Wait` stays until the operator confirms ("Next" control) — further scans are rejected with `repeat_full`.
- Duplicates inside one repeat step are rejected (`duplicate_in_step`) unless `allowDuplicates()` on the step.

## 8. Edge cases

| Case | Behaviour |
|---|---|
| Two optional steps in a row | Skip test walks forward through consecutive optional steps to the first required step; the first one whose checks pass receives the code. |
| Optional step is last | No next step: the failure is a normal rejection. The set is complete without it. |
| Optional step check fails and next step also fails | Rejected with the optional step's message. |
| Operator clicks a step | Pointer moves there (today's behaviour); the next scan is checked against that step. |
| Edited (typed) value with server work | `syncEditedValue()` goes through `handleStepScan` with source `manual` (new `ScanInput::Manual`? — open question 4); without server work, today's `$wire.set`. |
| `validateAll` fails with `JumpToStep` | Values kept; failing step cleared and active; camera resumes. |
| `autoSubmit` submit throws `ValidationException` | Result `submitted: false`; no reset; form errors render. |
| Livewire morph | Root keeps `wire:ignore`; the client state comes from the result `values`. |
| Same code twice inside cooldown | Dropped with `qr-scan-duplicate` (P1a). |
| Scan while checking | Router falls through (`accept()` false); camera frames paused. |

## 9. Backward compatibility

- `fields([...])` sequences without checks: identical `fields` payload, no server call, same events.
- Payload only gains keys (snapshot updated for added keys only).
- `statePathPrefix()` keeps working (still written by `handleStepScan`); deprecation stays a P2 item.
- No config key renamed.

## 10. Testing strategy

**Pest (PR A):**
- "fields(['a','b']) yields the same payload as steps([ScanStep::make('a'), …])" (acceptance P1.8), incl. keyed and `{key,label}` arrays.
- `ScanStep` unit tests (label default, checks, optional).
- `ScanCheckPipeline` keeps `HandleScanTest` green (refactor guard).
- Livewire E2E "invalid step 2 blocks advance; complete valid sequence calls submit once" (acceptance P1.9).
- Optional skip (decision 1), validateAll keyed/unkeyed/closure, `JumpToStep` vs `Restart` (decision 2), `onCompleted`, `ScanSequenceCompleted`, `autoSubmit` with a failing submit, `resetAfterSubmit`, `continuous`, config fallbacks, payload snapshot.

**Vitest (PR A):** server path vs unchanged local path, pointer moves to `next_step`, skip, rejection message, invalid jump, completed + reset, duplicate cooldown, router refusal while checking.

**PR B:** Vitest + Pest "three bursts fill steps po, sku, lot in order; sticky po survives reset"; repeat Advance/Wait; duplicates in a repeat step.

## 11. PR breakdown

| PR | Content | First failing test |
|---|---|---|
| A | P1.8 + P1.9: `ScanStep`, `steps()`, `fields()` adapter, `ScanCheckPipeline` extraction, `handleStepScan`, optional skip, `validateAllUsing`, `onInvalidSequence`, `onCompleted`, `autoSubmit`, `resetAfterSubmit`, `continuous`, `ScanSequenceCompleted`, config `sequence.*` (minus `repeat_complete`), JS server path, docs | "fields(['a','b']) yields the same payload as steps([ScanStep::make('a'), …])", then "invalid step 2 blocks advance; complete valid sequence calls submit once" |
| B | P1.10: router step ownership, `sticky()`, `repeat(n)`, `RepeatComplete`, `sequence.repeat_complete`, example warehouse/BOM demo hooks, docs | "three bursts fill steps po, sku, lot in order; sticky po survives reset" |

## 12. Open questions

1. **Unkeyed `validateAllUsing` failure:** jump to the last step (proposed) or the first step?
2. **`autoSubmit` method:** call a Livewire method by name (`'submit'` default, proposed) or dispatch the browser form submit (`requestSubmit()`)? The server call keeps it one request and testable in Pest.
3. **Normal submit:** should PR A add an opt-in rule so a manual form submit re-runs step checks and `validateAllUsing` (like P1a's `ScanChecksRule`)?
4. **Typed corrections:** new `ScanInput::Manual` source for edited values, or reuse `Hardware`?
5. **`resetAfterSubmit` default:** `false` (proposed, matches today) or `true` when `autoSubmit` is on?
