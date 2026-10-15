# P0 Scanner Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix the P0 bugs and blockers in the scanner, hardware-scanner, and option-payload code without breaking v0.2 users.

**Architecture:** Each bug is a hypothesis. It is reproduced with a failing Vitest (happy-dom) or Pest test, fixed minimally, and then proven with red → green → revert-red → restore-green. Stricter behaviour ships behind config with backward-compatible defaults.

**Tech Stack:** PHP 8.2+ / Filament v5 / Pest / PHPStan / Pint; Alpine components in `resources/js` tested with Vitest + happy-dom; Vite build committed to `resources/dist`.

**Spec:** `/workspace/filament-qr-code-plan.md` (review plan, section F, P0.1–P0.6). It is not committed to the repo.

## Global Constraints
- The public PHP API and browser event names stay backward compatible. New options are additive.
- New config keys are snake_case. Defaults preserve v0.2 behaviour: `hardware_scanner.min_fast_keys = 1` and `hardware_scanner.fallback_to_any_input = true`.
- Spatie PHP rules (typed, early returns, no `else`, one `->` per line once a chain breaks) and Spatie JS rules (`const`, `===`, full names, shorthand methods).
- Gate before push: `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`, `npx vitest run`, and `npm run build`, all run fresh and all at exit 0.

## Review Focus
1. Nested Livewire paths (`items.0.sku`) and names containing CSS metacharacters must resolve rather than silently fail.
2. A held-down key (auto-repeat) followed by Enter must never be treated as a scan.
3. Manual typing in a QrScanner must not send one Livewire request per keystroke.
4. Configured listener `fields()` must stay the routing priority when the opt-out flag is on.
5. The payload refactor must not drop or rename any key the Alpine components read.

---

### Task P0.1: QrScanner double sync / per-keystroke requests
**Files:** Modify `resources/js/qr-scanner.js`. Test `resources/js/qr-scanner.test.js`.
- [x] Write a failing test: one scan → exactly one `$wire.set`; five value changes from typing → zero `$wire.set` calls.
- [x] Run it and expect FAIL (2 and 5 calls).
- [x] Remove the `$watch('value')` → `$wire.set` bridge. Entangle plus `wire:model` already sync typed values. Keep the single explicit live `$wire.set` per scan, so `afterStateUpdated` hooks (`rejectWhen`, `normalizeUsing`) still run.
- [x] Run, expect PASS, prove revert-red, then commit.

### Task P0.2: Burst detection hardening
**Files:** `resources/js/qr-hardware-scanner.js`, `qr-scanner.js`, `qr-sequence.js`, `qr-collector.js`, `src/Concerns/HasHardwareScanner.php`, `src/Support/ScanOptions.php`, `config/qr-code.php`. Tests in `resources/js/qr-hardware-scanner.test.js` and `tests/Feature/ScanOptionsPayloadTest.php`.
**Produces:** `createHardwareScannerHandler({ minFastKeys = 1 })`, `HasHardwareScanner::getMinFastKeys(): int`, `hardwareScanner(..., minFastKeys:)`, and payload key `minFastKeys`.
- [x] Failing tests: auto-repeat (`event.repeat`) `aaaa` + Enter emits nothing; with `minFastKeys: 3`, `AB` + Enter emits nothing while `ABCDE` + Enter emits; the payload exposes `minFastKeys` from config.
- [x] Implement, verify, revert-red, commit.

### Task P0.3: Listener fallback to any empty input
**Files:** `resources/js/qr-hardware-scanner.js`, `src/Forms/Components/QrHardwareScannerListener.php`, `config/qr-code.php`. Test `resources/js/qr-hardware-scanner-listener.test.js`.
- [x] Failing tests: with `fallbackToAnyInput: false` and an empty search input in a form, the burst writes nothing and dispatches `qr-scan-unrouted`. The default (`true`) keeps the v0.2 fill and also dispatches nothing extra.
- [x] Implement, verify, revert-red, commit.

### Task P0.4: Selector escaping, exact matching, Livewire 2 `.defer` leftovers
**Files:** create `resources/js/field-locator.js`; modify `qr-scanner.js` and `qr-hardware-scanner.js`. Test `resources/js/field-locator.test.js` and `qr-scanner.test.js`.
- [x] Failing tests: `advanceFocus('sku')` must not focus `parent_sku`; `items.0.sku` must resolve by id; `getStatePath()` must read `wire:model.blur` / `wire:model.live.debounce.500ms`.
- [x] Implement, verify, revert-red, commit.

### Task P0.5: Lazy `distinctItems()` closure + `caption()` Closure + dead ternary
**Files:** `src/Forms/Components/QrCollector.php`, `QrCodeDisplay.php`, `QrScanSequence.php`. Tests `tests/Feature/QrCollectorTest.php` and `ComponentBehaviorTest.php`.
- [x] Failing tests: `distinctItems(fn (Get $get) => $get('strict'))` is evaluated at validation time against form state; `caption(fn () => 'X')` returns 'X'.
- [x] Implement, verify, revert-red, commit.

### Task P0.6: Trait-contributed scan payloads + `Support\ConfigValue`
**Files:** `src/Support/ScanOptions.php`, `src/Support/ConfigValue.php` (new), `src/Concerns/*`. Test `tests/Feature/ScanPayloadCharacterizationTest.php`.
- [x] Characterization: snapshot every component's payload. It must be green before and after.
- [x] Failing test: a component's `getComponentScanPayload()` extras are merged into the payload.
- [x] Refactor, verify, revert-red for the new behaviour, commit.

---

## Execution ledger
- Ruling: added `happy-dom` (dev) and `resources/js/alpine-test-harness.js` so Alpine components can be tested in Vitest. Why: the bugs live in the DOM and Livewire glue. Cost if wrong: one dev dependency. The harness fires `$watch` synchronously on change, which approximates Alpine's async watcher semantics.
- Ruling: P0.3 keeps `fallback_to_any_input = true` by default (it is documented v0.2 behaviour in `docs/scanning.md`). The hazard is fixed opt-in, and flipping the default is deferred to 1.0. Cost if wrong: users must opt in to get the safe routing.
- Ruling: `event.repeat` is ignored unconditionally (P0.2). Scanners never emit auto-repeat, so this only removes false positives. It is noted as a behaviour change.
- Ruling: P0.4 drops the substring fallbacks (`[wire:model*=]`, `[name*=]`) in favour of exact or `.<name>` suffix matching. It is noted as a behaviour change in CHANGELOG.
- Observation (not fixed, out of scope): `QrScanSequence::make(['employee' => 'Employee badge'])` re-capitalises a provided label to "Employee Badge". The characterization snapshot pins the current behaviour.
- Final verification (fresh run): Pest 94 passed; PHPStan no errors; Pint passed; Vitest 27 passed (6 files); `npm run build` exit 0 with a clean tree (dist reproducible).
