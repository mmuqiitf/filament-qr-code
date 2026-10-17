# P1b PR 2: Router Migration and `hardwareInput(scope:)` Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move every keyboard-wedge consumer (`QrHardwareScannerListener`, `QrCollector`, `QrScanSequence`, `QrScanner`) onto the input router from PR 1, so one burst is handled exactly once, and add `QrScanner::hardwareInput(scope:)` with a `hardware_scanner.default_scope` config default.

**Architecture:** Each Alpine component registers one router target in `init()` and unregisters it in `destroy()`; no component owns a `keydown` listener any more. The router gains three small behaviours the migration needs: a target may refuse a burst (`deliver()` returns `false`) and the router falls through to the next candidate; a target may claim a focused element it doesn't contain (`ownsFocus`, used by the listener for its registered inputs); and a listener candidate inside a page-scope target is left to that target. PHP adds one enum, one fluent method on `QrScanner`, one payload key and one config key.

**Tech Stack:** Alpine component objects, Vitest 5 + happy-dom with `alpine-test-harness.js`, Pest 4, Filament v5, Vite.

**Spec:** `docs/superpowers/specs/2026-10-17-p1b-input-router-continuous-mode-design.md`, sections 4.2, 4.3, 4.4 (`mode` on existing events), 5.1 (`HardwareInputScope`), 5.2 (`hardwareInput`, deprecation), 5.4, 6 (`default_scope`), 7, 8, and rows 2–3 of section 11 (merged into this PR by the owner).

## Global Constraints

- JS: `.agents/skills/spatie-javascript` (four-space indent, single quotes, `const`, strict equality, named exports, full names). PHP: `.agents/skills/spatie-laravel-php` (strict types, TitleCase enum cases, `snake_case` config keys, typed fluent setters returning `static`, config fallback via `ConfigValue`).
- No PHP public API is removed or renamed. `suppressWhenGlobalListener()`, `isSuppressedWhenGlobalListenerActive()` and the JS `isGlobalHardwareListenerActive()` stay and are marked `@deprecated`; the payload key `suppressWhenGlobalListenerActive` stays.
- Routing order is unchanged from PR 1: modal → focused → first listening (DOM order) → fallback → `qr-scan-unrouted`.
- A target's `deliver()` returning `false` means "refused": no `qr-scan-routed`, no burst id consumed, no stray-text restore; the router tries the next candidate. The router's min-length re-check is a refusal too.
- Existing events keep their names and keys. `qr-scan-accepted`, `qr-scan-rejected`, `qr-scan-duplicate` and `qr-scanned` from `QrScanner` gain `mode: 'single'` (continuous comes in PR B).
- `QrScanner` default scope is `focus` (today's field-scoped capture). `hardware_scanner.default_scope` invalid → `focus`.
- Busy-field one-slot queue and `qr-scan-dropped` are **not** in this PR: the spec puts them in continuous-mode handling (section 4.5, PR B). Here a checking field returns `accept() === false` and the burst falls through.
- Only the scanner payload gains a key (`hardwareScope`); listener, collector, collect-action and sequence payload snapshots stay identical.
- `resources/dist` is committed and CI checks it: run `npm run build` and commit dist in the task that changes shipped JS.
- TDD: each task starts with its named failing test, run and seen to fail.
- Verification: `npm test`, `npm run build`, `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`; then the example app against this branch locally.

## Files

| File | Responsibility |
|---|---|
| Modify `resources/js/input-router.js` | refusal fall-through, `ownsFocus`, candidate de-duplication, `hasTargetOfKind()` |
| Modify `resources/js/input-router.test.js` | new router tests; PR 1 "skips candidates inside another target" narrowed to page-scope owners |
| Modify `resources/js/qr-hardware-scanner.js` | listener registers a router target; `handleGlobalScan(value, preferredInput)`; deprecated `isGlobalHardwareListenerActive()` |
| Modify `resources/js/qr-collector.js`, `resources/js/qr-sequence.js` | register page targets instead of `window` keydown |
| Modify `resources/js/qr-scanner.js` | register a field target (`scope` from payload), `accept` false while checking, `isModalOpen`, `mode` on events |
| Create `resources/js/router-migration.test.js` | cross-component tests (listener + collector, sequence, scanner) |
| Modify existing listener/focus/collector tests | reset the shared router between tests |
| Create `src/Enums/HardwareInputScope.php` | `Focus`, `Page` |
| Modify `src/Forms/Components/QrScanner.php` | `hardwareInput()`, `getHardwareInputScope()`, payload `hardwareScope` |
| Modify `src/Concerns/HasHardwareScanner.php` | `@deprecated` on suppression methods |
| Modify `config/qr-code.php` | `hardware_scanner.default_scope` |
| Create `tests/Feature/HardwareInputScopeTest.php` | Pest |
| Modify scanner payload snapshot, `docs/scanning.md`, `docs/reference.md`, `CHANGELOG.md` | |

---

### Task 1: Router — refusal fall-through, focus ownership, candidate de-duplication

**Interfaces:** Produces target fields `ownsFocus?: (element) => boolean`; `deliver()` may return `false`; `router.hasTargetOfKind(kind): boolean`.

- [ ] **Step 1: Failing tests** (`input-router.test.js`):
  - "falls through to the next target when the chosen one refuses the burst"
  - "falls through when the focused target's minimum length rejects the burst"
  - "a target that owns the focused element wins as the focused target"
  - "leaves a listener candidate to the page-scope target that contains it" (replaces "skips candidates that sit inside another registered target"; a candidate inside a *focus-scope* target stays a listener candidate so today's listener → scanner handoff keeps working)
  - "hasTargetOfKind reports registered kinds"
- [ ] **Step 2:** `npx vitest run resources/js/input-router.test.js` — FAIL.
- [ ] **Step 3:** Implement: `tryDeliver()` returns `false` on refusal before consuming an id; step 2 checks containing targets first, then `ownsFocus`; candidate filter uses page-scope owners only.
- [ ] **Step 4:** PASS. **Step 5:** commit `feat: let router targets refuse bursts and claim focus`.

### Task 2: Listener, collector and sequence on the router

- [ ] **Step 1: Failing test** (`router-migration.test.js`): "listener and collector on one page deliver a burst exactly once"; plus "sequence receives a burst through the router", "listener no longer adds its own window keydown listener".
- [ ] **Step 2:** run — FAIL (both components handle the burst).
- [ ] **Step 3:** Listener registers `{ kind: 'listener', scope: 'page', element: $root, candidates, ownsFocus, fallback: fallbackToAnyInput, detection, deliver }`; `deliver(code, { element })` calls `handleGlobalScan(code, element)`; its own origin tracking is removed (the router restores). Collector and sequence register `{ kind: 'collector' | 'sequence', scope: 'page' }`. Existing listener/focus tests get `afterEach(() => inputRouter().destroy())`.
- [ ] **Step 4:** full `npm test` PASS. **Step 5:** commit `feat: move listener, collector and sequence onto the input router`.

### Task 3: `QrScanner` on the router, `mode` on events

- [ ] **Step 1: Failing tests:** "focused scanner field wins over a listener that registers it", "a checking scanner refuses the burst so it falls through", "scanner events carry mode single", "open camera modal owns hardware bursts".
- [ ] **Step 2–4:** remove the field `keydown` listener; register `{ kind: 'field', scope: hardwareScope ?? 'focus', name, accept: () => !this.isChecking, isModalOpen: () => this.isModalOpen, deliver: (code) => { this.handleScanResult(code, 'hardware'); } }`; add `mode` to the four events.
- [ ] **Step 5:** commit `feat: route QrScanner hardware input through the router`.

### Task 4: `hardwareInput(scope:)` and `hardware_scanner.default_scope`

- [ ] **Step 1: Failing tests:** Pest `HardwareInputScopeTest` — "defaults to focus", "hardwareInput(scope: Page) sets the payload", "reads hardware_scanner.default_scope", "invalid config falls back to focus", "hardwareInput(enabled: false) disables the hardware scanner"; Vitest "page-scope scanner receives a burst when nothing is focused".
- [ ] **Step 2–4:** enum, method, config key, payload; update the scanner payload snapshot for the added key only.
- [ ] **Step 5:** commit `feat: add QrScanner hardwareInput scope`.

### Task 5: Deprecations, docs, build, verification

- [ ] `@deprecated` docblocks (PHP + JS); `docs/scanning.md` replaces the double-handling warning; `reference.md` documents `hardwareInput()`; CHANGELOG "Unreleased".
- [ ] `npm run build`, commit `resources/dist` if changed; full verification; example app check (local path repository, not committed).
