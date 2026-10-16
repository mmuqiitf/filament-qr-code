# P1a PR 4: Browser Scan Pipeline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the `QrScanner` browser component call the PR 1–3 server hook once per scan when the field has server checks, and act on the result: duplicate suppression, an in-flight guard with camera pause, clear on reject, one `qr-scan-rejected` event, and `scan_failed` for broken calls. Hardware bursts from the page-global listener reach the same path.

**Architecture:** Pure helpers live in a new `resources/js/scan-pipeline.js` (deduper, result normalisation, the `$wire` call), so they can be tested without Alpine. `resources/js/qr-scanner.js` `handleScanResult(text, source)` becomes the single entry point for camera, upload and hardware: sanitize → dedupe → (no server check: today's path, unchanged) or (server check: in-flight guard → `requestScanCheck()` → apply accepted/rejected). The page-global listener in `qr-hardware-scanner.js` hands a burst to a server-checked field through a cancelable `qr-scan-deliver` DOM event instead of writing `$wire.set`. PHP only adds payload keys.

**Tech Stack:** Alpine.js components (plain objects), Livewire 3 `$wire.callSchemaComponentMethod`, html5-qrcode 2.3.8 (`pause(true)`/`resume()`), Vitest with happy-dom and `resources/js/alpine-test-harness.js`, Pest, Vite.

**Spec:** `docs/superpowers/specs/2026-10-16-p1a-server-scan-validation-design.md`, sections 3.2, 5.1, 5.2, 5.4, 9 items 1–2, 7 (Vitest and Livewire E2E bullets) and row 4 of section 8.

## Global Constraints

- JS follows `.agents/skills/spatie-javascript`: four-space indent, single quotes, 120 columns, `const` by default, `let` only when reassigned, strict equality, shorthand object methods, destructuring, no abbreviated names except in one-line arrows.
- PHP follows `.agents/skills/spatie-laravel-php` (strict types, typed returns, early returns).
- The browser calls exactly `this.$wire.callSchemaComponentMethod(componentKey, 'handleScan', { code, source })`, at most once per delivered scan.
- Server path only when `serverCheck === true` **and** `componentKey` is set **and** `$wire` exists. Otherwise the existing path runs unchanged (one `$wire.set`, success beep, `qr-scanned`, `advanceFocus`).
- `cooldownMs` comes from the PHP payload (`getCooldownMs()`: 0 without checks, `qr-code.scan.cooldown_ms` with checks). `0` never drops a scan.
- `qr-scan-rejected` is dispatched **once**, by the browser, with `{ value, field, source, message }`. The server never dispatches it for `handleScan` (PR 3).
- A rejected promise, a `null` result or a malformed result becomes `{ passes: false, message: scanFailedMessage }`, and the error is logged with `console.error`.
- Feedback in this PR: success beep on accept (existing `emitScanFeedback`); **no** sound on reject or duplicate. Error tones, vibration patterns and flash are PR 5.
- Browser events only gain fields; existing names and detail keys stay.
- Test-writing rule (from the PR 3 ledger): never record calls through `use (&$var)` inside a PHP arrow function `fn () =>` — use `function () use (&$var)` for every closure in the chain, or record into an object. In JS, record into a `const` array and push; don't reassign captured counters.
- `resources/dist` is committed and CI checks "Vite build is up to date": run `npm run build` and commit `resources/dist` in the task that changes shipped JS.
- TDD: each task starts with its named failing test, run and seen to fail for the stated reason.
- Verification: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`.

## Files

| File | Responsibility |
|---|---|
| Modify `src/Concerns/InteractsWithScans.php` | Payload keys `cooldownMs`, `clearOnReject`, `scanFailedMessage` |
| Create `resources/js/scan-pipeline.js` | `createScanDeduper()`, `normalizeScanResult()`, `requestScanCheck()` |
| Create `resources/js/scan-pipeline.test.js` | Pure helper tests |
| Modify `resources/js/alpine-test-harness.js` | `createWireSpy({ respond })` gains `methodCalls` and `callSchemaComponentMethod` |
| Modify `resources/js/qr-scanner.js` | Async `handleScanResult(text, source)`, server path, dedupe, in-flight guard, camera pause/resume, `qr-scan-deliver` handling |
| Create `resources/js/qr-scanner-pipeline.test.js` | Component tests for the server path |
| Modify `resources/js/qr-hardware-scanner.js` | Global listener hands bursts to server-checked fields via `qr-scan-deliver` |
| Modify `resources/js/qr-hardware-scanner-listener.test.js` | Stopgap tests |
| Modify `tests/Feature/HandleScanTest.php`, snapshot for the `scanner` row | Payload keys |
| Create `tests/Feature/ScanPipelineE2ETest.php` | Livewire E2E |
| Modify `docs/reference.md`, `docs/scanning.md` | Browser events and behaviour |
| Modify `resources/dist/*` | Rebuilt bundle |

---

### Task 1: Payload keys `cooldownMs`, `clearOnReject`, `scanFailedMessage`

**Files:**
- Modify: `src/Concerns/InteractsWithScans.php` (`getScanChecksScanPayload()`)
- Modify: `tests/Feature/HandleScanTest.php`
- Modify: `tests/.pest/snapshots/Feature/ScanPayloadCharacterizationTest/it_keeps_every_scan_surface_payload_stable_with_data_set__dataset__scanner____Closure_Object____.snap`

**Interfaces:**
- Consumes: `getCooldownMs()` (PR 2), `shouldClearOnReject()` (PR 3), translation `filament-qr-code::ui.scan_failed` (PR 2).
- Produces: payload `{ serverCheck: bool, cooldownMs: int, clearOnReject: bool, scanFailedMessage: string, componentKey?: string }`. Tasks 3–5 read these as `qrScannerComponent()` options of the same names.

- [ ] **Step 1: Write the failing test** (append to `tests/Feature/HandleScanTest.php`)

First failing test: **"scanner payload includes cooldownMs, clearOnReject and scanFailedMessage"**.

```php
it('scanner payload includes cooldownMs, clearOnReject and scanFailedMessage', function (): void {
    expect(QrScanner::make('sku')->getScanPayload())
        ->toHaveKey('cooldownMs', 0)
        ->toHaveKey('clearOnReject', true)
        ->toHaveKey('scanFailedMessage', 'The scan could not be checked. Please try again.');

    expect(QrScanner::make('sku')->validateUsing(['max:5'])->clearOnReject(false)->getScanPayload())
        ->toHaveKey('serverCheck', true)
        ->toHaveKey('cooldownMs', 1500)
        ->toHaveKey('clearOnReject', false);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/HandleScanTest.php --filter=cooldownMs`
Expected: FAIL with `Failed asserting that an array has the key 'cooldownMs'`.

- [ ] **Step 3: Write minimal implementation**

In `getScanChecksScanPayload()`, replace the first line with:

```php
        $payload = [
            'serverCheck' => $this->hasServerScanChecks(),
            'cooldownMs' => $this->getCooldownMs(),
            'clearOnReject' => $this->shouldClearOnReject(),
            'scanFailedMessage' => __('filament-qr-code::ui.scan_failed'),
        ];
```

Update the return docblock to `@return array{serverCheck: bool, cooldownMs: int, clearOnReject: bool, scanFailedMessage: string, componentKey?: string}`.

- [ ] **Step 4: Run tests and update the snapshot**

Run: `vendor/bin/pest tests/Feature/HandleScanTest.php`
Expected: PASS.
Run: `vendor/bin/pest tests/Feature/ScanPayloadCharacterizationTest.php`
Expected: only the `scanner` row FAILS, with exactly three added lines (`"clearOnReject": true`, `"cooldownMs": 0`, `"scanFailedMessage": "The scan could not be checked. Please try again."`). Anything else: stop and investigate.
Run: `vendor/bin/pest tests/Feature/ScanPayloadCharacterizationTest.php --update-snapshots`, then again without the flag. Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Concerns/InteractsWithScans.php tests/Feature/HandleScanTest.php tests/.pest/snapshots
git commit -m "feat: send cooldown, clear-on-reject and failure message to the browser"
```

---

### Task 2: `scan-pipeline.js` pure helpers

**Files:**
- Create: `resources/js/scan-pipeline.js`
- Test: `resources/js/scan-pipeline.test.js`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `createScanDeduper({ now = () => Date.now() } = {})` → `{ isDuplicate(code, cooldownMs): boolean }`. A non-duplicate call records `code` and the time; the window is measured from the last non-dropped delivery of that code (fixed, not sliding). `cooldownMs <= 0` never reports a duplicate.
  - `normalizeScanResult(result, { code, source, scanFailedMessage })` → `{ passes, code, source, message, recordKey }`.
  - `async requestScanCheck(wire, { componentKey, code, source, scanFailedMessage })` → normalized result; never throws.

- [ ] **Step 1: Write the failing tests**

First failing test: **"same code within 1500 ms is dropped"**.

```js
import { afterEach, describe, expect, it, vi } from 'vitest';
import { createScanDeduper, normalizeScanResult, requestScanCheck } from './scan-pipeline.js';

function clock(start = 0) {
    const state = { now: start };

    return { state, now: () => state.now };
}

describe('createScanDeduper', () => {
    it('same code within 1500 ms is dropped', () => {
        const { state, now } = clock();
        const deduper = createScanDeduper({ now });

        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
        state.now = 1499;
        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(true);
        state.now = 1500;
        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
    });

    it('never drops a different code', () => {
        const { now } = clock();
        const deduper = createScanDeduper({ now });

        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
        expect(deduper.isDuplicate('SKU-2', 1500)).toBe(false);
        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
    });

    it('never drops anything when the cooldown is 0', () => {
        const { now } = clock();
        const deduper = createScanDeduper({ now });

        expect(deduper.isDuplicate('SKU-1', 0)).toBe(false);
        expect(deduper.isDuplicate('SKU-1', 0)).toBe(false);
    });
});

describe('normalizeScanResult', () => {
    const context = { code: 'SKU-1', source: 'camera', scanFailedMessage: 'Check failed' };

    it('maps a server result to the client shape', () => {
        expect(normalizeScanResult(
            { passes: true, code: 'SKU-1', source: 'hardware', message: null, record_key: 7 },
            context,
        )).toEqual({ passes: true, code: 'SKU-1', source: 'hardware', message: null, recordKey: 7 });
    });

    it.each([[null], [undefined], ['oops'], [{}], [{ passes: 'yes' }]])('treats %j as a failed check', (result) => {
        expect(normalizeScanResult(result, context)).toEqual({
            passes: false,
            code: 'SKU-1',
            source: 'camera',
            message: 'Check failed',
            recordKey: null,
        });
    });
});

describe('requestScanCheck', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('calls handleScan once through callSchemaComponentMethod', async () => {
        const calls = [];
        const wire = {
            callSchemaComponentMethod(...args) {
                calls.push(args);

                return Promise.resolve({ passes: true, code: 'SKU-1', source: 'camera', message: null, record_key: null });
            },
        };

        const result = await requestScanCheck(wire, {
            componentKey: 'form.sku',
            code: 'SKU-1',
            source: 'camera',
            scanFailedMessage: 'Check failed',
        });

        expect(calls).toEqual([['form.sku', 'handleScan', { code: 'SKU-1', source: 'camera' }]]);
        expect(result.passes).toBe(true);
    });

    it('turns a rejected call into a failed check and logs the error', async () => {
        const errors = vi.spyOn(console, 'error').mockImplementation(() => {});
        const wire = { callSchemaComponentMethod: () => Promise.reject(new Error('offline')) };

        const result = await requestScanCheck(wire, {
            componentKey: 'form.sku',
            code: 'SKU-1',
            source: 'camera',
            scanFailedMessage: 'Check failed',
        });

        expect(result).toEqual({ passes: false, code: 'SKU-1', source: 'camera', message: 'Check failed', recordKey: null });
        expect(errors).toHaveBeenCalledTimes(1);
    });
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npx vitest run resources/js/scan-pipeline.test.js`
Expected: FAIL with `Failed to resolve import "./scan-pipeline.js"`.

- [ ] **Step 3: Write minimal implementation**

`resources/js/scan-pipeline.js`:

```js
export function createScanDeduper({ now = () => Date.now() } = {}) {
    let lastCode = null;
    let lastDeliveredAt = 0;

    return {
        isDuplicate(code, cooldownMs) {
            const currentTime = now();

            if (cooldownMs > 0 && code === lastCode && currentTime - lastDeliveredAt < cooldownMs) {
                return true;
            }

            lastCode = code;
            lastDeliveredAt = currentTime;

            return false;
        },
    };
}

export function normalizeScanResult(result, { code, source, scanFailedMessage }) {
    if (result === null || typeof result !== 'object' || typeof result.passes !== 'boolean') {
        return { passes: false, code, source, message: scanFailedMessage, recordKey: null };
    }

    return {
        passes: result.passes,
        code: result.code ?? code,
        source: result.source ?? source,
        message: result.message ?? null,
        recordKey: result.record_key ?? null,
    };
}

export async function requestScanCheck(wire, { componentKey, code, source, scanFailedMessage }) {
    try {
        const result = await wire.callSchemaComponentMethod(componentKey, 'handleScan', { code, source });

        return normalizeScanResult(result, { code, source, scanFailedMessage });
    } catch (error) {
        console.error('QR scan check failed:', error);

        return normalizeScanResult(null, { code, source, scanFailedMessage });
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `npx vitest run resources/js/scan-pipeline.test.js`
Expected: PASS (all tests).

- [ ] **Step 5: Commit**

```bash
git add resources/js/scan-pipeline.js resources/js/scan-pipeline.test.js
git commit -m "feat: add browser scan pipeline helpers"
```

---

### Task 3: Server path in `handleScanResult()` (accept)

**Files:**
- Modify: `resources/js/alpine-test-harness.js`
- Modify: `resources/js/qr-scanner.js`
- Test: `resources/js/qr-scanner-pipeline.test.js`

**Interfaces:**
- Consumes: Task 1 options, Task 2 `requestScanCheck`.
- Produces:
  - `qrScannerComponent()` options `serverCheck = false`, `componentKey = null`, `cooldownMs = 0`, `clearOnReject = true`, `scanFailedMessage = 'The scan could not be checked. Please try again.'`.
  - Component state `isChecking: false`.
  - `async handleScanResult(scannedText, source = 'camera')` → `Promise<{ passes: boolean, ignored?: boolean, ... }>`. The non-server path still does its `$wire.set` synchronously before the first `await`, so existing synchronous tests keep passing.
  - Methods `acceptLocally(code, source, field)`, `applyAccepted(result, field)`, `applyRejected(result, field)` (Task 4 fills `applyRejected`).
  - `createWireSpy({ respond } = {})` → `{ calls, methodCalls, set(path, value), callSchemaComponentMethod(key, method, args) }`; `respond(key, method, args)` supplies the return value (default `null`).

- [ ] **Step 1: Extend the harness** (test infrastructure, no behaviour)

Replace `createWireSpy` in `resources/js/alpine-test-harness.js`:

```js
export function createWireSpy({ respond = () => null } = {}) {
    const calls = [];
    const methodCalls = [];

    return {
        calls,
        methodCalls,
        set(path, value) {
            calls.push([path, value]);
        },
        callSchemaComponentMethod(componentKey, method, args) {
            methodCalls.push([componentKey, method, args]);

            return respond(componentKey, method, args);
        },
    };
}
```

Run: `npm test`. Expected: PASS (existing callers use `createWireSpy()` unchanged).

- [ ] **Step 2: Write the failing tests**

First failing test: **"calls handleScan once via callSchemaComponentMethod when serverCheck is true and accepts the result"**.

`resources/js/qr-scanner-pipeline.test.js`:

```js
// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

function accepted(code, extra = {}) {
    return { passes: true, code, source: 'camera', message: null, record_key: null, ...extra };
}

function rejected(code, message) {
    return { passes: false, code, source: 'camera', message, record_key: null };
}

function mountChecked({ respond = (key, method, { code }) => Promise.resolve(accepted(code)), ...options } = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"></div>';
    const element = document.querySelector('[data-field-name="sku"]');
    const wire = createWireSpy({ respond });
    const scanner = mountComponent(
        qrScannerComponent({
            statePath: 'data.sku',
            sound: false,
            vibrate: false,
            hardwareScanner: false,
            serverCheck: true,
            componentKey: 'form.sku',
            cooldownMs: 1500,
            ...options,
        }),
        { element, wire },
    );

    return { scanner, wire };
}

function captureWindowEvent(name) {
    const seen = [];
    const listener = (event) => seen.push(event.detail);
    window.addEventListener(name, listener);
    seenListeners.push([name, listener]);

    return seen;
}

const seenListeners = [];

afterEach(() => {
    document.body.innerHTML = '';
    seenListeners.splice(0).forEach(([name, listener]) => window.removeEventListener(name, listener));
});

describe('qrScanner server checks', () => {
    it('calls handleScan once via callSchemaComponentMethod when serverCheck is true and accepts the result', async () => {
        const { scanner, wire } = mountChecked({
            respond: () => Promise.resolve(accepted('SKU-1', { source: 'hardware', record_key: 7 })),
        });
        const acceptedEvents = captureWindowEvent('qr-scan-accepted');
        const scannedEvents = captureWindowEvent('qr-scanned');

        const result = await scanner.handleScanResult('  SKU-1 ', 'hardware');

        expect(wire.methodCalls).toEqual([['form.sku', 'handleScan', { code: 'SKU-1', source: 'hardware' }]]);
        expect(wire.calls).toEqual([]);
        expect(result.passes).toBe(true);
        expect(scanner.value).toBe('SKU-1');
        expect(acceptedEvents).toEqual([{ value: 'SKU-1', field: 'sku', source: 'hardware', recordKey: 7 }]);
        expect(scannedEvents).toEqual([{ value: 'SKU-1', field: 'sku', nextField: null, source: 'hardware', recordKey: 7 }]);
    });

    it('keeps the unconfigured path unchanged: no server call, one $wire.set', () => {
        const { scanner, wire } = mountChecked({ serverCheck: false, cooldownMs: 0 });

        scanner.handleScanResult('PRD-1001');

        expect(wire.methodCalls).toEqual([]);
        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
    });

    it('falls back to the unconfigured path when the component key is missing', () => {
        const { scanner, wire } = mountChecked({ componentKey: null });

        scanner.handleScanResult('PRD-1001');

        expect(wire.methodCalls).toEqual([]);
        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
    });
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `npx vitest run resources/js/qr-scanner-pipeline.test.js`
Expected: the first test FAILS (`wire.methodCalls` is `[]` and `wire.calls` holds the `$wire.set`), because `handleScanResult` ignores `serverCheck`. The other two pass already and stay as regression guards.

- [ ] **Step 4: Write minimal implementation**

In `resources/js/qr-scanner.js`:

1. Import: `import { requestScanCheck } from './scan-pipeline.js';` (Task 5 adds `createScanDeduper`)
2. Add options after `insecureMessage`:

```js
    serverCheck = false,
    componentKey = null,
    cooldownMs = 0,
    clearOnReject = true,
    scanFailedMessage = 'The scan could not be checked. Please try again.',
```

3. Add `isChecking: false,` to the state block.
4. Replace `handleScanResult(scannedText) { ... }` with:

```js
        async handleScanResult(scannedText, source = 'camera') {
            const code = sanitizeScannedValue(scannedText);

            if (!code) {
                return { passes: false, ignored: true };
            }

            const field = this.$el.getAttribute('data-field-name') || null;

            if (!serverCheck || !componentKey || !this.$wire) {
                this.acceptLocally(code, source, field);

                return { passes: true, code, source };
            }

            this.isChecking = true;

            try {
                const result = await requestScanCheck(this.$wire, { componentKey, code, source, scanFailedMessage });

                if (result.passes) {
                    this.applyAccepted(result, field);
                } else {
                    this.applyRejected(result, field);
                }

                return result;
            } finally {
                this.isChecking = false;
            }
        },

        acceptLocally(code, source, field) {
            this.value = code;

            const path = this.getStatePath();
            if (this.$wire && path) {
                this.$wire.set(path, code);
            }

            emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });

            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: { value: code, field, nextField, source },
            }));

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        applyAccepted({ code, source, recordKey }, field) {
            // The server already wrote the state; this keeps the deferred entangle in step without a request.
            this.value = code;

            emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });

            window.dispatchEvent(new CustomEvent('qr-scan-accepted', { detail: { value: code, field, source, recordKey } }));
            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: { value: code, field, nextField, source, recordKey },
            }));

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        applyRejected() {},
```

The `qr-scanned` detail in `acceptLocally` gains `source`; check `resources/js/qr-scanner.test.js` still passes (it asserts `$wire` calls, not the event detail).

- [ ] **Step 5: Run tests to verify they pass**

Run: `npx vitest run resources/js/qr-scanner-pipeline.test.js && npm test`
Expected: PASS, including the existing `qr-scanner.test.js` and `qr-scanner-file.test.js`.

- [ ] **Step 6: Commit**

```bash
git add resources/js/alpine-test-harness.js resources/js/qr-scanner.js resources/js/qr-scanner-pipeline.test.js
git commit -m "feat: check live scans on the server from the scanner field"
```

---

### Task 4: Reject, clear and `scan_failed`

**Files:**
- Modify: `resources/js/qr-scanner.js` (`applyRejected`)
- Test: `resources/js/qr-scanner-pipeline.test.js` (append)

**Interfaces:**
- Consumes: Task 3 `handleScanResult()`, `normalizeScanResult` via `requestScanCheck`.
- Produces: `applyRejected({ code, source, message }, field)` clears `this.value` when `clearOnReject`, dispatches `qr-scan-rejected` once with `{ value, field, source, message }`, plays no sound, doesn't advance focus.

- [ ] **Step 1: Write the failing tests** (append inside the `describe`)

First failing test: **"rejected result clears the value and dispatches qr-scan-rejected once"**.

```js
    it('rejected result clears the value and dispatches qr-scan-rejected once', async () => {
        const { scanner } = mountChecked({ respond: () => Promise.resolve(rejected('SKU-404', 'No record found for "SKU-404".')) });
        const rejectedEvents = captureWindowEvent('qr-scan-rejected');
        const scannedEvents = captureWindowEvent('qr-scanned');
        scanner.value = 'OLD';

        const result = await scanner.handleScanResult('SKU-404');

        expect(result.passes).toBe(false);
        expect(scanner.value).toBe('');
        expect(rejectedEvents).toEqual([
            { value: 'SKU-404', field: 'sku', source: 'camera', message: 'No record found for "SKU-404".' },
        ]);
        expect(scannedEvents).toEqual([]);
    });

    it('keeps the value on reject when clearOnReject is off', async () => {
        const { scanner } = mountChecked({ clearOnReject: false, respond: () => Promise.resolve(rejected('X', 'Nope')) });
        scanner.value = 'OLD';

        await scanner.handleScanResult('X');

        expect(scanner.value).toBe('OLD');
    });

    it.each([
        ['a null result', () => Promise.resolve(null)],
        ['a rejected call', () => Promise.reject(new Error('offline'))],
    ])('turns %s into a rejected scan with the scan_failed message', async (label, respond) => {
        const errors = [];
        const originalError = console.error;
        console.error = (...args) => errors.push(args);

        try {
            const { scanner } = mountChecked({ respond, scanFailedMessage: 'The scan could not be checked. Please try again.' });
            const rejectedEvents = captureWindowEvent('qr-scan-rejected');

            await scanner.handleScanResult('SKU-1');

            expect(rejectedEvents.map(({ message }) => message)).toEqual(['The scan could not be checked. Please try again.']);
        } finally {
            console.error = originalError;
        }
    });
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npx vitest run resources/js/qr-scanner-pipeline.test.js -t "reject|scan_failed"`
Expected: FAIL: `rejectedEvents` is `[]` and `scanner.value` is still `'OLD'`, because `applyRejected` is empty.

- [ ] **Step 3: Write minimal implementation**

Replace `applyRejected() {},` with:

```js
        applyRejected({ code, source, message }, field) {
            if (clearOnReject) {
                this.value = '';
            }

            window.dispatchEvent(new CustomEvent('qr-scan-rejected', { detail: { value: code, field, source, message } }));
        },
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `npx vitest run resources/js/qr-scanner-pipeline.test.js`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/js/qr-scanner.js resources/js/qr-scanner-pipeline.test.js
git commit -m "feat: clear and announce rejected scans in the browser"
```

---

### Task 5: Dedupe, in-flight guard and camera pause

**Files:**
- Modify: `resources/js/qr-scanner.js` (camera decode callback, `scanFile`)
- Test: `resources/js/qr-scanner-pipeline.test.js` (append)

**Interfaces:**
- Consumes: Task 2 `createScanDeduper`, Task 3 `handleScanResult()` and `isChecking`.
- Produces: the dedupe and in-flight guards inside `handleScanResult()`, and `async onCameraDecode(decodedText)`. Without a server check it keeps today's behaviour (handle, then close the modal). With one, it pauses the camera (`this.html5Qrcode.pause(true)`), awaits the check, closes the modal on pass, resumes the camera (`this.html5Qrcode.resume()`) on reject so the operator can rescan. Decodes during a check are ignored by `handleScanResult`'s `isChecking` guard. `scanFile` passes `'upload'`.

- [ ] **Step 1: Write the failing tests** (append inside the `describe`)

First failing test: **"same code within cooldown is dropped and emits qr-scan-duplicate"**.

```js
    it('same code within cooldown is dropped and emits qr-scan-duplicate', async () => {
        const { scanner, wire } = mountChecked();
        const duplicates = captureWindowEvent('qr-scan-duplicate');

        await scanner.handleScanResult('SKU-1');
        await scanner.handleScanResult('SKU-1');

        expect(wire.methodCalls).toHaveLength(1);
        expect(duplicates).toEqual([{ value: 'SKU-1', field: 'sku', source: 'camera' }]);
    });

    it('ignores a second scan while a check is in flight', async () => {
        const pending = [];
        const { scanner, wire } = mountChecked({
            respond: (key, method, { code }) => new Promise((resolve) => pending.push(() => resolve(accepted(code)))),
        });

        const first = scanner.handleScanResult('SKU-1');
        const second = await scanner.handleScanResult('SKU-2');

        expect(second).toEqual({ passes: false, ignored: true });
        expect(wire.methodCalls).toHaveLength(1);

        pending[0]();
        await first;

        expect(scanner.isChecking).toBe(false);
    });

    it('pauses the camera during a check and resumes it after a reject', async () => {
        const cameraCalls = [];
        const { scanner } = mountChecked({ respond: () => Promise.resolve(rejected('X', 'Nope')) });
        scanner.html5Qrcode = {
            pause: (shouldPauseVideo) => cameraCalls.push(['pause', shouldPauseVideo]),
            resume: () => cameraCalls.push(['resume']),
        };
        scanner.isScanning = true;
        scanner.isModalOpen = true;

        await scanner.onCameraDecode('X');

        expect(cameraCalls).toEqual([['pause', true], ['resume']]);
        expect(scanner.isModalOpen).toBe(true);
    });

    it('closes the scanner after an accepted camera scan', async () => {
        const { scanner } = mountChecked();
        const closes = [];
        scanner.html5Qrcode = { pause: () => {}, resume: () => {} };
        scanner.closeScannerModal = () => closes.push('close');

        await scanner.onCameraDecode('SKU-1');

        expect(closes).toEqual(['close']);
    });
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `npx vitest run resources/js/qr-scanner-pipeline.test.js -t "duplicate|in flight|camera"`
Expected: all four FAIL. Duplicate: `wire.methodCalls` has length 2 and no `qr-scan-duplicate` event. In flight: `second` is a real result and `methodCalls` has length 2. Camera: `scanner.onCameraDecode is not a function`.

- [ ] **Step 3: Write minimal implementation**

Import `createScanDeduper` alongside `requestScanCheck`, and after `const storageKey = ...;` add:

```js
    const deduper = createScanDeduper();
```

In `handleScanResult()`, replace the empty-code guard and add the dedupe block before the `if (!serverCheck ...` line:

```js
            if (!code || this.isChecking) {
                return { passes: false, ignored: true };
            }

            const field = this.$el.getAttribute('data-field-name') || null;

            if (deduper.isDuplicate(code, cooldownMs)) {
                window.dispatchEvent(new CustomEvent('qr-scan-duplicate', { detail: { value: code, field, source } }));

                return { passes: false, ignored: true };
            }
```

Add the camera method:

```js
        async onCameraDecode(decodedText) {
            if (!serverCheck || !componentKey) {
                await this.handleScanResult(decodedText, 'camera');
                this.closeScannerModal();

                return;
            }

            if (this.isChecking) {
                return;
            }

            this.pauseCamera();

            const result = await this.handleScanResult(decodedText, 'camera');

            if (result.ignored) {
                this.resumeCamera();

                return;
            }

            if (result.passes) {
                this.closeScannerModal();

                return;
            }

            this.resumeCamera();
        },

        pauseCamera() {
            try {
                this.html5Qrcode?.pause?.(true);
            } catch (error) {
                console.debug('QR camera pause failed:', error);
            }
        },

        resumeCamera() {
            try {
                this.html5Qrcode?.resume?.();
            } catch (error) {
                console.debug('QR camera resume failed:', error);
            }
        },
```

In `startScan()`, replace the decode callback body:

```js
                    (decodedText) => {
                        this.onCameraDecode(decodedText);
                    },
```

In `scanFile()`, replace the last two lines:

```js
            await this.handleScanResult(decodedText, 'upload');
            this.closeScannerModal();
```

In `init()`, the field-scoped hardware handler: `onScan: (scannedValue) => { this.handleScanResult(scannedValue, 'hardware'); },`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `npx vitest run resources/js/qr-scanner-pipeline.test.js && npm test`
Expected: PASS, including `qr-scanner-file.test.js` (upload still closes the modal).

- [ ] **Step 5: Commit**

```bash
git add resources/js/qr-scanner.js resources/js/qr-scanner-pipeline.test.js
git commit -m "feat: dedupe scans and pause the camera while a check runs"
```

---

### Task 6: Hardware stopgap for the page-global listener

**Files:**
- Modify: `resources/js/qr-hardware-scanner.js` (`handleGlobalScan`)
- Modify: `resources/js/qr-scanner.js` (`init`/`destroy`: listen for `qr-scan-deliver`)
- Test: `resources/js/qr-hardware-scanner-listener.test.js`, `resources/js/qr-scanner-pipeline.test.js` (append)

**Interfaces:**
- Consumes: Task 3 `handleScanResult(code, 'hardware')`.
- Produces:
  - The global listener dispatches `new CustomEvent('qr-scan-deliver', { bubbles: true, cancelable: true, detail: { value } })` on the chosen `targetInput` after `restoreBurstOrigin()` and `focus()`. If the event is cancelled (`dispatchEvent` returns `false`), the listener skips the DOM write, its beep, `$wire.set` and auto-advance, and still dispatches `qr-hardware-scanned`.
  - `qrScanner` adds a `qr-scan-deliver` listener on `this.$el`: when `serverCheck && componentKey`, it calls `event.preventDefault()` and `this.handleScanResult(event.detail.value, 'hardware')`. Otherwise it does nothing, so the listener's existing path runs.

- [ ] **Step 1: Write the failing tests**

First failing test: **"global listener hands a burst to a server-checked scanner field instead of writing $wire.set"** (append to `qr-hardware-scanner-listener.test.js`; add `createWireSpy` to the harness import):

```js
describe('qrHardwareScannerListener server-checked fields (P1a stopgap)', () => {
    it('global listener hands a burst to a server-checked scanner field instead of writing $wire.set', () => {
        document.body.innerHTML = '<form><div data-field-name="sku"><input type="text" name="sku" wire:model="data.sku"></div></form>';
        const input = document.querySelector('[name="sku"]');
        const delivered = [];
        input.closest('[data-field-name]').addEventListener('qr-scan-deliver', (event) => {
            event.preventDefault();
            delivered.push(event.detail.value);
        });
        const wire = createWireSpy();
        const element = document.createElement('div');
        document.body.appendChild(element);
        const listener = mountComponent(
            qrHardwareScannerListenerComponent({ fields: ['sku'], sound: false, vibrate: false, autoFocusNext: false }),
            { element, wire },
        );

        listener.handleGlobalScan('SKU-1');

        expect(delivered).toEqual(['SKU-1']);
        expect(input.value).toBe('');
        expect(wire.calls).toEqual([]);
    });

    it('keeps writing directly when no field claims the burst', () => {
        document.body.innerHTML = '<form><input type="text" name="sku" wire:model="data.sku"></form>';
        const wire = createWireSpy();
        const element = document.createElement('div');
        document.body.appendChild(element);
        const listener = mountComponent(
            qrHardwareScannerListenerComponent({ fields: ['sku'], sound: false, vibrate: false, autoFocusNext: false }),
            { element, wire },
        );

        listener.handleGlobalScan('SKU-1');

        expect(document.querySelector('[name="sku"]').value).toBe('SKU-1');
        expect(wire.calls).toEqual([['data.sku', 'SKU-1']]);
    });
});
```

Append to `qr-scanner-pipeline.test.js` (inside the `describe`):

```js
    it('claims qr-scan-deliver events and checks them as hardware scans', async () => {
        const { scanner, wire } = mountChecked();
        const input = document.querySelector('[data-field-name="sku"] input');
        const event = new CustomEvent('qr-scan-deliver', { bubbles: true, cancelable: true, detail: { value: 'SKU-9' } });

        const notCancelled = input.dispatchEvent(event);
        await Promise.resolve();

        expect(notCancelled).toBe(false);
        expect(wire.methodCalls).toEqual([['form.sku', 'handleScan', { code: 'SKU-9', source: 'hardware' }]]);
        expect(scanner.isChecking).toBe(false);
    });

    it('does not claim qr-scan-deliver without server checks', () => {
        mountChecked({ serverCheck: false });
        const input = document.querySelector('[data-field-name="sku"] input');

        expect(input.dispatchEvent(new CustomEvent('qr-scan-deliver', { bubbles: true, cancelable: true, detail: { value: 'X' } }))).toBe(true);
    });
```

If `scanner.isChecking` is still `true` after one microtask, replace `await Promise.resolve()` with `await new Promise((resolve) => setTimeout(resolve, 0))` and record it in the ledger.

- [ ] **Step 2: Run tests to verify they fail**

Run: `npx vitest run resources/js/qr-hardware-scanner-listener.test.js resources/js/qr-scanner-pipeline.test.js -t "qr-scan-deliver|server-checked|claims"`
Expected: "global listener hands a burst…" FAILS (`delivered` is `[]`, `input.value` is `'SKU-1'`); "claims qr-scan-deliver…" FAILS (`notCancelled` is `true`). "keeps writing directly…" and "does not claim…" pass already as guards.

- [ ] **Step 3: Implement the listener side**

In `handleGlobalScan`, inside `if (targetInput) {`, right after `targetInput.focus();`:

```js
                const deliverEvent = new CustomEvent('qr-scan-deliver', {
                    bubbles: true,
                    cancelable: true,
                    detail: { value: scannedValue },
                });

                if (!targetInput.dispatchEvent(deliverEvent)) {
                    window.dispatchEvent(new CustomEvent('qr-hardware-scanned', {
                        detail: { value: scannedValue, field: targetFieldName },
                    }));

                    return;
                }
```

- [ ] **Step 4: Implement the field side**

In `qr-scanner.js` state add `boundDeliverHandler: null,`. At the end of `init()`:

```js
            this.boundDeliverHandler = (event) => {
                if (!serverCheck || !componentKey) {
                    return;
                }

                event.preventDefault();
                this.handleScanResult(event.detail?.value, 'hardware');
            };
            this.$el.addEventListener('qr-scan-deliver', this.boundDeliverHandler);
```

At the start of `destroy()`:

```js
            if (this.boundDeliverHandler) {
                this.$el.removeEventListener('qr-scan-deliver', this.boundDeliverHandler);
                this.boundDeliverHandler = null;
            }
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `npm test`
Expected: PASS (all Vitest files).

- [ ] **Step 6: Commit**

```bash
git add resources/js/qr-hardware-scanner.js resources/js/qr-scanner.js resources/js/qr-hardware-scanner-listener.test.js resources/js/qr-scanner-pipeline.test.js
git commit -m "feat: route global hardware scans through server-checked fields"
```

---

### Task 7: Livewire E2E, docs and bundle

**Files:**
- Create: `tests/Feature/ScanPipelineE2ETest.php`
- Modify: `docs/reference.md` (browser events table), `docs/scanning.md`
- Modify: `resources/dist/filament-qr-code.js` (and any other file `npm run build` changes)

**Interfaces:**
- Consumes: PR 2 fixtures `Tests\Fixtures\ScanChecksTestComponent`, `Tests\Fixtures\ScanCheckProduct`; Task 1 payload.
- Produces: an end-to-end proof that a rendered form exposes what the browser needs and that the browser's exact call works.

- [ ] **Step 1: Write the E2E test**

First failing test: **"a rendered lookup scanner exposes the browser contract and handles the browser's handleScan call"**.

```php
<?php

declare(strict_types=1);

use Filament\Schemas\Components\Utilities\Set;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);

it('a rendered lookup scanner exposes the browser contract and handles the browser\'s handleScan call', function (): void {
    $test = ScanChecksTestComponent::test(function (QrScanner $scanner): QrScanner {
        return $scanner
            ->lookup(ScanCheckProduct::class, 'sku')
            ->onScanned(function (ScanResult $result, Set $set): void {
                $set('note', "stock {$result->record->stock}");
            });
    });

    $scanner = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);

    expect($scanner->getScanPayload())->toMatchArray([
        'serverCheck' => true,
        'componentKey' => 'form.sku',
        'cooldownMs' => 1500,
        'clearOnReject' => true,
        'scanFailedMessage' => 'The scan could not be checked. Please try again.',
    ]);

    $test->assertSeeHtml('qrScanner(')
        ->assertSeeHtml('data-field-name="sku"');

    $test->call('callSchemaComponentMethod', 'form.sku', 'handleScan', ['code' => 'SKU-1', 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === true)
        ->assertSet('data.sku', 'SKU-1')
        ->assertSet('data.note', 'stock 5');

    $test->call('callSchemaComponentMethod', 'form.sku', 'handleScan', ['code' => 'SKU-404', 'source' => 'hardware'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'No record found for "SKU-404".')
        ->assertSet('data.sku', null)
        ->assertNotDispatched('qr-scan-rejected');
});

it('renders an unconfigured scanner without server checks or cooldown', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner): QrScanner => $scanner);

    $scanner = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);

    expect($scanner->getScanPayload())->toMatchArray(['serverCheck' => false, 'cooldownMs' => 0]);
});
```

- [ ] **Step 2: Run the test**

Run: `vendor/bin/pest tests/Feature/ScanPipelineE2ETest.php`
Expected after Task 1: PASS. To show its red, run it with Task 1's payload change reverted (`git stash` is not needed: temporarily delete the `cooldownMs` line in `getScanChecksScanPayload()`), see `toMatchArray` fail on `cooldownMs`, restore the line, see PASS. Record the red in the PR evidence.

- [ ] **Step 3: Update docs**

In `docs/reference.md`, replace the `qr-scanned` and `qr-scan-rejected` rows and add two rows:

```markdown
| `qr-scanned`              | `{ value, field, nextField, source, recordKey? }` | any accepted `QrScanner` scan |
| `qr-scan-accepted`        | `{ value, field, source, recordKey }` | a server-checked scan passed |
| `qr-scan-rejected`        | `{ message, value?, field?, source? }` | a server-checked scan failed, or a `rejectWhen()` predicate matched |
| `qr-scan-duplicate`       | `{ value, field, source }`    | same code again within the field's cooldown |
```

Append to the "Server-side checks" section of `docs/scanning.md`:

```markdown
### In the browser

- A field with server checks sends each scan to the server once and waits for the answer. While it waits, the camera pauses and further scans are ignored.
- Accepted: the value stays, the success beep plays, and `qr-scan-accepted` and `qr-scanned` fire. Camera scans close the scanner.
- Rejected: the field clears (unless `clearOnReject(false)`), `qr-scan-rejected` fires once with the message, and the camera resumes so you can scan again.
- If the request fails, the scan is rejected with "The scan could not be checked. Please try again."
- The same code scanned again within the cooldown is ignored and fires `qr-scan-duplicate`.
- A page-level `QrHardwareScannerListener` hands bursts to a server-checked field, so wedge scans are checked too.
```

- [ ] **Step 4: Rebuild the bundle**

Run: `npm run build`
Expected: exit 0; `git status` shows `resources/dist/filament-qr-code.js` changed.

- [ ] **Step 5: Full verification**

Run each, fresh: `composer test`, `composer analyse`, `composer test-style`, `npm test`, `npm run build`.
Expected: all exit 0; a second `npm run build` leaves `git status` clean apart from already-staged changes.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/ScanPipelineE2ETest.php docs/reference.md docs/scanning.md resources/dist
git commit -m "test: cover the browser scan contract end to end; rebuild assets"
```

---

## Spec coverage

| Requirement | Task |
|---|---|
| Payload `cooldownMs`, `clearOnReject` (+ `scanFailedMessage` for the failure message, spec 5.1) | 1 |
| One `callSchemaComponentMethod(componentKey, 'handleScan', { code, source })` when `serverCheck` | 2, 3 |
| Unconfigured fields unchanged | 3 (guard tests) |
| Dedupe within `cooldownMs` with `qr-scan-duplicate` | 2, 5 |
| In-flight guard; camera pause, resume on reject, close on pass | 3, 5 |
| Clear on reject; `qr-scan-rejected` once from the browser | 4 |
| Failed call or `null` → rejected with `scan_failed`, logged | 2, 4 |
| Hardware via `handleScanResult(code, 'hardware')` (field-scoped and page-global stopgap) | 5, 6 |
| Livewire E2E | 7 |
| Error/duplicate sound, vibration pattern, flash | PR 5 (out of scope) |
