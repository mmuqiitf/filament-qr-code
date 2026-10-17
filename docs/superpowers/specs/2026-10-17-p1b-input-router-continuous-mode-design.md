# P1b: Hardware input router and continuous mode (design spec)

- **Date:** 2026-10-17
- **Status:** Approved by owner 2026-10-17 (binding decisions in section 2.3, resolved questions in section 12)
- **Roadmap items:** P1.6 and P1.7 in the review plan (section F)
- **Scope:** Sub-project 2 of 3 of P1. Builds on P1a (`handleScan`, `ScanResult`, cooldown, outcome feedback), shipped in v0.4.0.

## 1. Problem

Keyboard-wedge capture is spread over four independent `keydown` listeners, each with its own
`createHardwareScannerHandler()` buffer:

| Component | Listener | Today |
|---|---|---|
| `QrScanner` (`qr-scanner.js`) | field element | handles bursts typed into its own input |
| `QrScanSequence` (`qr-sequence.js`) | `window` | page-global |
| `QrCollector` (`qr-collector.js`) | `window` | page-global |
| `QrHardwareScannerListener` (`qr-hardware-scanner.js`) | `window` | page-global, writes into a focused or first empty registered field |

Ownership is coordinated only by a global counter (`__filamentQrCodeGlobalHardwareListeners`) and
the `suppressWhenGlobalListenerActive` flag: field handlers stand down whenever any listener is
mounted. That leaves known gaps:

- **Double handling.** A collector plus a listener on one page both buffer and both deliver the same burst. `docs/scanning.md` warns about it instead of preventing it.
- **Focused scan field loses.** With a listener mounted, a focused `QrScanner` stands down, so its `handleScan` runs only through the P1a stopgap (`qr-scan-deliver`), and only when the listener happens to pick that input.
- **No page-level opt-in for `QrScanner`.** A scanner field can't say "catch wedge scans anywhere on this page" without a separate listener component.
- **Single-shot only.** After an accepted scan the code stays in the field, and the camera modal closes. POS line entry (scan, scan, scan, each one adds a cart line in `onScanned()`) needs the field to be ready again with no manual clearing.

## 2. Goals and non-goals

**Goals**

1. One page-wide input router owns all keyboard-wedge capture: one buffer, one `keydown` listener, so **one burst is handled exactly once**.
2. A focused scan field always wins over listener fields.
3. `QrScanner::hardwareInput(scope:)` lets a field listen only while focused (today's behaviour) or page-wide.
4. `QrHardwareScannerListener`, `QrCollector` and `QrScanSequence` are migrated onto the router with no change to their public PHP API.
5. `QrScanner::mode(ScanMode::Continuous)`: every accepted code calls `onScanned()` once, and the field is immediately ready for the next scan.
6. No behaviour change for apps that don't opt in (section 8).

**Out of scope**

- Sequence step routing without focus, `sticky()`, `repeat(n)` (P1.10). The sequence only registers as a router target here; its internal step logic is unchanged.
- `ScanStep`, per-step validation, `autoSubmit` (P1.8–P1.9).
- Cart-line merging (the app's job in `onScanned()`, owner decision in P1a).
- Continuous mode for `QrCollector` (it already collects many codes) and for upload input (one file is one scan).
- Removing deprecated options (`suppressWhenGlobalListener`) — planned for 1.0.

### 2.1 Requirements traced

- Plan requirement 2 (multi-field wedge flows): goals 1–4.
- POS line entry: goal 5.

### 2.2 Conventions

PHP follows `.agents/skills/spatie-laravel-php` (enums with TitleCase cases, `snake_case`
config keys, typed fluent setters returning `static`, config default with per-field override via
`ConfigValue`). JS follows `.agents/skills/spatie-javascript` (named exports, camelCase,
no classes where a factory will do, kebab-case DOM event names). New window events use the
existing `qr-*` kebab-case prefix.

### 2.3 Owner decisions (binding, 2026-10-17)

1. When no scan field has focus, a hardware scan goes to the **first listening field on the page** (DOM order).
2. Continuous mode: the field display after an accepted scan is configurable, **empty by default**.
3. Continuous mode: rejected-scan behaviour is configurable; **default shows the error and keeps scanning** (alternative: pause until dismissed).
4. Continuous mode: camera modal stay-open is configurable; **default closes after each scan**.

## 3. Approach

A module-level singleton router in a new `resources/js/input-router.js`. Components no longer
create burst handlers; they register a **target** and receive whole, sanitized codes.

### 3.1 Rejected alternatives

| Alternative | Why rejected |
|---|---|
| Keep per-component handlers, improve the global counter (claim/release tokens per burst) | Every handler still buffers every key; ownership has to be agreed after the fact by several buffers that may disagree on burst boundaries (different thresholds). Races are inherent. |
| Event bubbling: one window listener dispatches `qr-hardware-burst`, components call `preventDefault()` to claim | Claim order then depends on listener registration order, not on focus or DOM order, so decision 1 can't be guaranteed. Harder to test. |
| Livewire/server-side routing | Routing depends on `document.activeElement` and DOM order, which only the browser knows; a round trip per keystroke burst adds latency. |

## 4. JS design

### 4.1 Router module

```js
// resources/js/input-router.js
export function createInputRouter({ now = () => Date.now(), win = window, doc = document } = {}) { ... }

export function inputRouter() {
    // One router per page, even if two bundles load: stored on window.__filamentQrCodeInputRouter.
}
```

Public surface:

```js
const unregister = inputRouter().register({
    id,              // unique per Alpine component instance ($id)
    element,         // the field wrapper (x-data root) used for focus test and DOM order
    scope,           // 'focus' | 'page'
    kind,            // 'field' | 'listener' | 'collector' | 'sequence'
    detection,       // { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs, preventSubmit }
    containsFocus,   // (activeElement) => boolean, default element.contains(activeElement)
    candidates,      // optional () => Element[] (listener: its registered empty inputs)
    accept,          // optional (code) => boolean, false = skip me (e.g. disabled, checking)
    deliver,         // (code, { burstId, reason }) => void
});

inputRouter().isActive();   // replaces isGlobalHardwareListenerActive() (kept as an alias)
```

The router installs **one** `keydown` listener on `window` in the capture phase when the first
target registers and removes it when the last one unregisters. It reuses
`createHardwareScannerHandler()` unchanged as its single burst detector (keeps all P0 guards: IME,
modifiers, `repeat`, fast-streak, `scanTimeoutMs` flush, sanitization).

**Detection settings.** Targets may carry different tuning. The router uses the settings of the
target that currently has focus; otherwise the most permissive values across page targets
(largest `burstThresholdMs` and `scanTimeoutMs`, smallest `minFastKeys` and `minBarcodeLength`,
union of `terminators`). The chosen target's own `minBarcodeLength` is re-checked before delivery.
In practice all fields read the same `hardware_scanner.*` config.

### 4.2 Routing algorithm (per completed burst)

Each burst gets a `burstId`. Exactly one `deliver()` call or one `qr-scan-unrouted` event happens
per `burstId`.

1. **Camera modal open.** If a target reports an open camera modal (`target.isModalOpen()`), it owns the burst. The modal is the user's current task.
2. **Focused target.** If `document.activeElement` is inside a registered target (any scope), that target owns the burst (**focused scan field wins over listener fields**).
3. **First listening target.** Otherwise, collect page-scope targets that `accept(code)`, expand a listener into its `candidates()` (each ranked by its own input's DOM position, not the listener element; resolution 1) (its registered inputs that are empty, enabled and not read-only, matching today's rule), sort all by DOM order (`compareDocumentPosition`), and deliver to the first (**decision 1**).
4. **Legacy fallback.** If nothing matched and a mounted listener has `fallbackToAnyInput` on, the listener receives it and writes into the first empty form input (v0.4 behaviour).
5. **Unrouted.** Dispatch `qr-scan-unrouted` `{ value }`.

**Typed-character cleanup.** Wedge keys land in whatever input has focus before the burst is
recognised. The router records the focused text entry and its value at burst start (the
`burstOrigin` logic now in the listener moves into the router). When the burst is delivered to a
target other than that input, the origin value is restored, as today, unless the input (or an ancestor) has `data-qr-ignore`. When the burst goes to the
focused target, the target's `deliver()` overwrites the value anyway.

**Submit prevention.** When a burst completes on a terminator and `preventSubmit` applies, the
router calls `preventDefault()`/`stopPropagation()` once (capture phase, so a form `Enter`
never submits).

### 4.3 Targets per component

| Component | scope | Notes |
|---|---|---|
| `QrScanner` | from `hardwareInput(scope:)`, default `focus` | `deliver` → `handleScanResult(code, 'hardware')` (local accept or `handleScan`). `accept` is false while `isChecking`, so the burst falls through to the next target rather than being lost. Exposes `isModalOpen()`. |
| `QrHardwareScannerListener` | `page` | Keeps its field list, `autoFocusNext`, `fallbackToAnyInput`. `candidates()` returns its registered empty inputs. `deliver` keeps the current write path including the P1a `qr-scan-deliver` handoff to a server-checked `QrScanner`. The listener's own `window` listener and global counter are removed. |
| `QrCollector` | `page` | `deliver` → existing `addCode()` path. |
| `QrScanSequence` | `page` | `deliver` → existing current-step path. Focus routing of steps is P1.10. |

A `QrScanner` that is itself a listener's registered field is registered once (as its own field
target); the listener skips candidates already registered as targets to avoid two owners.

### 4.4 Events

| Event (window) | When | Detail |
|---|---|---|
| `qr-scan-routed` (new) | a burst was delivered | `{ value, target: field name or null, kind, reason: 'modal' \| 'focus' \| 'first-listening' \| 'fallback', burstId }` |
| `qr-scan-unrouted` (existing) | no target | `{ value }` |
| `qr-scan-dropped` (new) | a scan arrived while the target was busy and its one-slot queue was full | `{ value, field, source }` |
| `qr-hardware-scanned` (existing) | listener wrote a value | unchanged |
| `qr-scan-accepted` / `qr-scan-rejected` / `qr-scan-duplicate` / `qr-scanned` (existing) | per scan | gain `mode: 'single' \| 'continuous'` |
| `qr-scan-paused` / `qr-scan-resumed` (new) | continuous with `pauseOnReject` | `{ field }` |

PHP events (`ScanAccepted`, `ScanRejected`, `QrCodeScanned`) are unchanged; in continuous mode
they fire once per accepted or rejected code, as today per scan.

### 4.5 Continuous mode in `qr-scanner.js`

New payload keys: `mode`, `continuousDisplay` (`'empty' | 'last_code'`), `pauseOnReject`,
`keepCameraOpen`.

- **Accept:** server already ran `onScanned()` and set state (section 5.3). Client sets `this.value` to `''` (empty) or the code (`last_code`), plays success feedback, keeps or restores focus on the field so the next wedge scan goes there, emits `qr-scan-accepted` with `mode`.
- **Reject, default:** error feedback and the message shown under the field (existing P1a rendering) until the next accepted scan; the field is cleared per `clearOnReject`; scanning continues.
- **Reject, `pauseOnReject`:** the field enters a `paused` state; the router's `accept()` returns false for it (bursts fall through to the next target or are unrouted), the camera is paused, and an inline "Scanning paused — dismiss to continue" control is shown. Dismiss (button, or `Escape` while focused) resumes. Emits `qr-scan-paused`/`qr-scan-resumed`.
- **Camera:** default closes the modal after each accepted scan (**decision 4**), as in single mode. With `keepCameraOpen()` the camera pauses during the check, then resumes; cooldown stops the same code firing repeatedly at 25 fps.
- **In-flight:** a scan arriving while `isChecking` is true is not dropped silently. For hardware it falls through the router (`accept` false); if no other target takes it, it is held in a one-slot queue and replayed after the check (rapid POS bursts). Camera frames are already paused during checks.

## 5. PHP design

### 5.1 New enums

```php
namespace Mmuqiitf\FilamentQrCode\Enums;

enum HardwareInputScope: string
{
    case Focus = 'focus';
    case Page = 'page';
}

enum ScanMode: string
{
    case Single = 'single';
    case Continuous = 'continuous';
}

enum ContinuousDisplay: string
{
    case Empty = 'empty';
    case LastCode = 'last_code';
}
```

### 5.2 Public API

```php
QrScanner::make('sku')
    ->hardwareInput(scope: HardwareInputScope::Page)   // or ->hardwareInput() = focus
    ->mode(ScanMode::Continuous)
    ->continuousDisplay(ContinuousDisplay::LastCode)    // default from config: Empty
    ->pauseOnReject()                                   // default from config: false
    ->keepCameraOpen()                                  // default from config: false
    ->lookup(Product::class, 'sku')
    ->onScanned(fn (Product $record, Get $get, Set $set) => $set('lines', addLine($get('lines'), $record)));
```

- `HasHardwareScanner::hardwareInput(HardwareInputScope|Closure $scope = HardwareInputScope::Focus, bool|Closure $enabled = true): static` and `getHardwareInputScope(): HardwareInputScope`. Added to the shared trait, but only `QrScanner` exposes a choice; listener, collector and sequence are always `Page`. `hardwareScanner(...)` keeps its signature for tuning.
- `QrScanner::mode(ScanMode|Closure $mode): static`, `getMode(): ScanMode`, `isContinuous(): bool`.
- `continuousDisplay(ContinuousDisplay|Closure $display): static`, `pauseOnReject(bool|Closure $condition = true): static`, `keepCameraOpen(bool|Closure $condition = true): static`, each with a getter falling back to config through `ConfigValue`. They have no effect in single mode.
- `suppressWhenGlobalListener()` stays accepted and is marked `@deprecated` (the router makes it unnecessary). Behaviour under the router: `false` on a focus-scoped field is equivalent to today's intent and needs no special handling.

### 5.3 `handleScan` in continuous mode

`isContinuous()` makes `hasServerScanChecks()` true, so every accepted code goes through
`handleScan` (one Livewire call per scan) even without rules or lookup; that is where `onScanned()`
runs. In `acceptScan()`:

1. `$this->state($result->code)`, `callAfterStateUpdated()`, `onScanned()` (callbacks see the code in state, so existing closures that read `$state` keep working).
2. Events as today.
3. If continuous and display is `Empty`: `$this->state(null)` without calling `afterStateUpdated` again.

A `Set` call by `onScanned()` on the scanner's own path is overridden by step 3; documented.
Reject path is unchanged (`onRejected`, `clearOnReject`).

**Submit.** With display `Empty` the field is blank at submit, so `ScanChecksRule` passes; using
`required()` on a continuous field is a configuration error and logs a one-time warning in local
env; it never throws (resolution 5). Continuous fields are normally `->dehydrated(false)`; the docs recommend it.

### 5.4 Payload

`getHardwareScannerScanPayload()` adds `hardwareScope`. `QrScanner` payload adds `mode`,
`continuousDisplay`, `pauseOnReject`, `keepCameraOpen`. `ScanPayloadCharacterizationTest` is
updated for added keys only.

## 6. Config

```php
'hardware_scanner' => [
    // existing keys unchanged
    'default_scope' => 'focus', // 'focus' | 'page' — QrScanner only
],

'scan' => [
    'cooldown_ms' => 1500,
    'clear_on_reject' => true,
    'continuous' => [
        'display' => 'empty',          // 'empty' | 'last_code'   (decision 2)
        'pause_on_reject' => false,    // false = show error, keep scanning (decision 3)
        'keep_camera_open' => false,   // false = close modal after each scan (decision 4)
        'cooldown_ms' => 300,          // duplicate window for continuous fields (resolution 2)
    ],
],
```

Invalid values fall back to the defaults (existing `ConfigValue` pattern, `tryFrom` for enums).

## 7. Edge cases

| Case | Behaviour |
|---|---|
| Two listeners on one page | Both register; candidates merged in DOM order; first empty registered field wins. One delivery. |
| Listener + page-scope `QrScanner` | DOM order decides; whichever is earlier is "first listening". |
| Listener + collector | Previously double-handled; now DOM order, one delivery. |
| Input marked `data-qr-ignore` | Never a burst origin to restore: its typed text is left as is (resolution 4). Routing still follows section 4.2. |
| Focused non-scan text input (search box) | Not a target, so step 3 applies: scan goes to first listening field and the typed characters are reverted in the search box. With no page targets, the burst stays as typed text (no target, nothing to revert) and `qr-scan-unrouted` fires. |
| Focused focus-scope `QrScanner` | Step 2, it wins. |
| Focus-scope `QrScanner` not focused, no page targets | Unrouted; characters stay where they were typed (today's behaviour). |
| Camera modal open | Step 1: modal owner gets hardware bursts. |
| Field disabled / read-only / checking / paused | `accept()` false, skipped; server also rejects disabled fields (P1a). |
| Rapid bursts (< check round trip) | Each burst routed independently; a busy continuous field holds one queued code (section 4.5); further codes while the queue is full are dropped and emit `qr-scan-dropped` `{ value, field, source }` (resolution 3). |
| Same code twice inside cooldown | Existing cooldown drops it (`qr-scan-duplicate`). Continuous fields use `scan.continuous.cooldown_ms` (300) unless `cooldown()` is set. |
| Livewire morph / SPA navigation | Targets unregister in `destroy()`; router removes its listener when empty; re-register on init. Registration is idempotent by `id`. |
| Two bundles loaded | Single router via window key. |
| Human typing in a scan field | Unchanged P0 guards; not a burst, nothing routed. |

## 8. Backward compatibility

- Default scope `focus` equals today's field-scoped `QrScanner` capture.
- Pages with one listener: same targets, same "focused registered field, else first empty registered field, else fallback" order.
- Pages with listener + collector/sequence: change from double handling to single handling (bug fix, noted in CHANGELOG).
- `createHardwareScannerHandler`, `isGlobalHardwareListenerActive` stay exported (`index.js`), the latter as an alias of `inputRouter().isActive()`.
- `mode` defaults to `Single`; no continuous payload effect.
- No config key is renamed or removed.

## 9. Testing strategy

TDD: each PR starts from the failing test named in section 11.

**Vitest `resources/js/input-router.test.js`** (jsdom, fake timers, synthetic `keydown` bursts helper from existing hardware tests):

- "focused scan field wins over listener fields; one burst handled once" (roadmap acceptance test)
- no focus → first page target in DOM order receives it (decision 1); listener candidates interleave by DOM position
- modal-open target wins over focused field
- listener + collector on one page: exactly one `deliver`
- busy (`accept` false) target is skipped
- focused search input: characters reverted, scan delivered elsewhere
- `qr-scan-routed` reasons; `qr-scan-unrouted` when no targets
- single `window` listener added/removed with first/last registration; two `inputRouter()` calls return the same instance
- slow typing never routes (regression of P0 guards)

**Vitest migration:** existing `qr-hardware-scanner-listener.test.js`, `qr-hardware-scanner-focus.test.js`, `qr-collector-channel.test.js`, `qr-scanner-pipeline.test.js` stay green with only setup changes.

**Vitest continuous (`qr-scanner-continuous.test.js`):** "continuous mode keeps state empty and calls onScanned per accepted code" (client half: three accepted results → value `''` each time, three `qr-scan-accepted`); `last_code` display; reject keeps scanning vs `pauseOnReject` blocks until dismiss; modal closes by default, stays open with `keepCameraOpen`; queued burst replayed after check.

**Pest:**

- `HandleScanContinuousTest`: three `handleScan` calls run `onScanned` three times with the right `$record`, state ends `null` (empty) or last code; no `afterStateUpdated` on the reset; continuous without checks still accepts via `handleScan`; events fire per code.
- `HardwareInputTest`: `hardwareInput()` payload scope, config `default_scope` fallback, invalid config → `focus`.
- Config fallbacks for `scan.continuous.*`; `ScanPayloadCharacterizationTest` additions.
- Livewire E2E (extend `LivewireFormE2ETest`): continuous lookup scanner adds lines to a `Repeater` sibling on three scans.

## 10. Example app (`/workspace/filament-qr-code-example`)

- **New page `PosCheckout`** (route name `posCheckout`, URL `/pos-checkout`): continuous `QrScanner` with `hardwareInput(scope: Page)` and `lookup(Product::class, 'sku')`; `onScanned` merges cart lines (quantity++ on same SKU, the app's job) in a `Repeater`; a toggle panel to switch `continuousDisplay`, `pauseOnReject`, `keepCameraOpen` live; a scan log driven by `qr-scan-routed`/`qr-scan-accepted`.
- **`CashierCheckout`** (existing listener demo): add a search box and a second focus-scope scanner to show "focused field wins" and "search box keeps its text".
- Seeded products with SKUs printed as barcodes on a printable test sheet (existing generator).

## 11. PR breakdown

| PR | Content | First failing test |
|---|---|---|
| 1 | `input-router.js` (router, routing, origin restore, events), unit tests only; no component uses it yet | "focused scan field wins over listener fields; one burst handled once" |
| 2 | Migrate `QrHardwareScannerListener`, `QrCollector`, `QrScanSequence`, `QrScanner` (focus scope) onto the router; remove global counter; alias `isGlobalHardwareListenerActive`; docs warning removed | "listener and collector on one page deliver a burst exactly once" |
| 3 | `HardwareInputScope`, `hardwareInput(scope:)`, `hardware_scanner.default_scope`, payload, page-scope `QrScanner` target, deprecate `suppressWhenGlobalListener` | "page-scope scanner receives a burst when nothing is focused" |
| 4 | `ScanMode`, `ContinuousDisplay`, `mode()` and continuous options, `scan.continuous.*` config, `handleScan` reset, client continuous handling | "continuous mode keeps state empty and calls onScanned per accepted code" |
| 5 | Example app `PosCheckout` + `CashierCheckout` additions, `docs/scanning.md` and `reference.md` | (example app Pest smoke test: page renders and continuous scan adds a line) |

PRs 1–3 are P1.6, PR 4 is P1.7, PR 5 docs/demo.

## 12. Resolved questions

Resolved by the owner on 2026-10-17 (PR #26 review):

1. **Listener ranking:** listener candidates are ranked by the DOM position of each registered input, not of the hidden listener element (section 4.2).
2. **Continuous cooldown:** continuous fields get their own default, `scan.continuous.cooldown_ms` = 300; other fields keep `scan.cooldown_ms` = 1500. `cooldown()` still overrides per field (section 6).
3. **Overflow while busy:** one-slot queue; further scans are dropped and dispatch `qr-scan-dropped` (sections 4.5, 7).
4. **Opt-out of stray-text restore:** inputs (or ancestors) with `data-qr-ignore` keep wedge text; the router never restores them (sections 4.2, 7).
5. **`required()` on continuous fields:** logs a one-time warning in local env only; never throws (section 5.3).
