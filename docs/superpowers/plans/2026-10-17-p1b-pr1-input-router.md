# P1b PR 1: Input Router Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a page-wide keyboard-wedge input router (`resources/js/input-router.js`) that owns one burst detector and delivers each burst to exactly one registered target, with full Vitest coverage. No component uses it yet.

**Architecture:** `createInputRouter({ win, doc })` returns a router that keeps a map of targets, installs one capture-phase `keydown` listener while any target is registered, feeds keys to a single `createHardwareScannerHandler()` (reused unchanged from `qr-hardware-scanner.js`), and on each completed burst runs the spec's routing order: modal owner → focused target → first listening candidate in DOM order → fallback target → `qr-scan-unrouted`. `inputRouter()` returns one shared instance per window. Stray characters typed into a focused text input are restored when the burst goes elsewhere, unless the input opts out with `data-qr-ignore`.

**Tech Stack:** Plain ES modules, Vitest 5 with happy-dom, fake timers.

**Spec:** `docs/superpowers/specs/2026-10-17-p1b-input-router-continuous-mode-design.md` (PR #26), sections 4.1, 4.2, 4.4 (`qr-scan-routed`, `qr-scan-unrouted`), 7, 9 (router bullets), 12 items 1 and 4, and row 1 of section 11.

## Global Constraints

- JS follows `.agents/skills/spatie-javascript`: four-space indent, single quotes, 120 columns, `const` by default, strict equality, shorthand methods, named exports, factories not classes, full names.
- One burst → exactly one `deliver()` call **or** one `qr-scan-unrouted` event.
- Routing order (spec 4.2): (1) target whose `isModalOpen()` is true; (2) target containing `document.activeElement` (any scope); (3) page-scope targets that `accept(code)`, a target with `candidates()` expanded into one entry per candidate input, all sorted by DOM position of the entry's element (resolution 1), candidates inside another registered target skipped; (4) first target (DOM order) with `fallback: true`; (5) `qr-scan-unrouted` `{ value }`.
- `accept(code) === false` skips a target at every step.
- `qr-scan-routed` detail: `{ value, target, kind, reason, burstId }`; `target` is the target's `name` or `null`; `reason` is `'modal' | 'focus' | 'first-listening' | 'fallback'`.
- `deliver(code, { burstId, reason, element })`; `element` is the chosen candidate input (or the target element).
- Burst origin = focused `INPUT`/`TEXTAREA` and its value at the first key after a gap longer than `burstThresholdMs`. Restored (value + `input` + `change` events) when the burst is delivered and the origin is neither inside the chosen target nor the chosen candidate element. Never restored when the origin or an ancestor has `data-qr-ignore` (resolution 4).
- Detection settings: focused target's `detection`; otherwise the most permissive across targets (max `burstThresholdMs`, max `scanTimeoutMs`, min `minFastKeys`, min `minBarcodeLength`, union `terminators`, `preventSubmit` true if any). Re-chosen only while the detector buffer is empty. The chosen target's own `minBarcodeLength` is re-checked before delivery.
- One `keydown` listener (capture) on `window` while ≥ 1 target; removed when 0. `register()` is idempotent by `id`, returns an unregister function.
- `inputRouter()` stores the instance on `window.__filamentQrCodeInputRouter`.
- Scope: router module + tests only. `index.js`, components and `resources/dist` are not changed (CI's "Vite build is up to date" stays green because the bundle doesn't import the module).
- TDD: each task starts with its named failing test, run and seen to fail.
- Verification: `npm test`, `npm run build` (dist must be unchanged), `composer test`, `composer analyse`, `composer test-style`.

## Files

| File | Responsibility |
|---|---|
| Create `resources/js/input-router.js` | `createInputRouter()`, `inputRouter()`, routing, origin restore, events |
| Create `resources/js/input-router.test.js` | Router tests with a `typeBurst()` helper (fake timers, 10 ms gaps, `Enter`) |

## Target shape (produced by Task 1, used by PR 2)

```js
{
    id: string,                 // required, unique
    element: Element,           // required; focus test + DOM order
    name: string | null,        // reported as `target` in qr-scan-routed
    kind: 'field' | 'listener' | 'collector' | 'sequence',
    scope: 'focus' | 'page',    // default 'focus'
    detection: { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs, preventSubmit },
    candidates: () => Element[],  // optional
    accept: (code) => boolean,    // optional, default true
    isModalOpen: () => boolean,   // optional
    fallback: boolean,            // optional
    deliver: (code, context) => void,
}
```

---

### Task 1: Focused target wins; one burst handled once

**Files:** Create `resources/js/input-router.js`, `resources/js/input-router.test.js`.

**Interfaces:** Produces `createInputRouter({ win = window, doc = document } = {})` → `{ register(target): () => void, isActive(): boolean, targetCount(): number, destroy(): void }`.

- [ ] **Step 1: Write the failing test** — `it('focused scan field wins over listener fields; one burst handled once')`: a page-scope listener target with a candidate input and a focus-scope field target whose input is focused; `typeBurst('PRD-1001')`; expect the field's `deliver` called once with `'PRD-1001'` and the listener's `deliver` not called.
- [ ] **Step 2: Run** `npx vitest run resources/js/input-router.test.js` — FAIL (module not found).
- [ ] **Step 3: Implement** targets map, capture listener, single detector, `routeBurst()` steps 1–2 and 5.
- [ ] **Step 4: Run** — PASS.
- [ ] **Step 5: Commit** `feat: add input router with focused-target routing`.

### Task 2: First listening target in DOM order, candidates, accept, fallback, unrouted

- [ ] **Step 1: Failing tests:**
  - "routes to the first page target in DOM order when nothing is focused"
  - "ranks listener candidates by each input's DOM position, not the listener element"
  - "skips candidates that sit inside another registered target"
  - "skips targets whose accept() returns false"
  - "ignores focus-scope targets that are not focused"
  - "uses the fallback target when no candidate matches"
  - "dispatches qr-scan-unrouted when nothing can take the burst"
- [ ] **Step 2: Run** — FAIL.
- [ ] **Step 3: Implement** step 3 and step 4 of routing.
- [ ] **Step 4: Run** — PASS. **Step 5: Commit** `feat: route unfocused bursts to the first listening target`.

### Task 3: Modal owner, events, stray-text restore

- [ ] **Step 1: Failing tests:**
  - "a target with an open camera modal wins over the focused field"
  - "dispatches qr-scan-routed with value, target, kind, reason and burstId"
  - "restores text typed into a focused search input when the burst goes elsewhere"
  - "leaves text in an input marked data-qr-ignore"
  - "does not restore when the burst goes to the focused target"
- [ ] **Step 2–4:** run (FAIL), implement origin capture/restore and event, run (PASS).
- [ ] **Step 5: Commit** `feat: restore stray wedge text and dispatch qr-scan-routed`.

### Task 4: Lifecycle, settings, singleton, P0 guards

- [ ] **Step 1: Failing tests:**
  - "adds one window keydown listener for the first target and removes it after the last"
  - "register is idempotent by id"
  - "inputRouter() returns the same instance for every call"
  - "uses the focused target's detection settings" (focused `burstThresholdMs: 100`, 70 ms gaps → delivered)
  - "uses the most permissive settings across targets when nothing is focused"
  - "re-checks the chosen target's minimum length"
  - "prevents the terminator's default action when a burst completes"
  - "never routes slow human typing"
- [ ] **Step 2–4:** run (FAIL), implement, run (PASS).
- [ ] **Step 5:** Run full verification (`npm test`, `npm run build` + `git status resources/dist` clean, `composer test`, `composer analyse`, `composer test-style`). Commit `feat: router lifecycle, settings and singleton`.
