# P1c PR A: Sequence Steps, Validation and Submit Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give `QrScanSequence` real steps (`ScanStep`, `steps()`, `fields()` adapter) with per-step server checks, optional-step auto-skip, a whole-set check, `onCompleted`, `autoSubmit`, `resetAfterSubmit` and sequence `continuous()`, all through one Livewire call per scan, while plain `fields()` sequences stay request-free and unchanged.

**Architecture:** The P1a rule → lookup → closure pipeline moves from `InteractsWithScans` into `Support\ScanCheckPipeline` (a plain object built from rules, closures and lookup config, run with an evaluator closure), used by `QrScanner` and by `ScanStep`. `QrScanSequence` gains a `HasSequenceSteps` trait (steps, completion options) and an exposed `handleStepScan(step, code, source)` that checks the step (and the next one for optional skips), writes state, and on completion runs `validateAllUsing`, `onCompleted`, `autoSubmit` and reset. It returns a `SequenceScanResult` array that drives `qr-sequence.js`.

**Tech Stack:** Filament v5 schema components, `#[ExposedLivewireMethod]` + `callSchemaComponentMethod`, Pest 4 + Livewire testing, Vitest 5 + happy-dom.

**Spec:** `docs/superpowers/specs/2026-10-17-p1c-sequences-design.md` sections 2–6, 8–10 and row A of section 11.

## Global Constraints

- PHP: `.agents/skills/spatie-laravel-php`; JS: `.agents/skills/spatie-javascript`.
- Plain `fields()` sequences: identical `fields` payload, no server call, same events; payload only gains keys.
- `steps()` and `fields()` produce identical payloads for the same keys and labels.
- One Livewire call per scan (`handleStepScan`), only when the sequence has server work: any step check, `validateAllUsing`, `onCompleted` or `autoSubmit`.
- Check order per step: rules → lookup → closures; first failure stops.
- Optional skip (decision 1): optional step fails → try the following steps through consecutive optional steps to the first required one; the first that passes receives the code; otherwise reject with the optional step's message.
- `validateAllUsing` failure (decision 2): default `InvalidSequenceBehavior::JumpToStep` (keep values, clear and activate the failing step); `Restart` clears all and goes to the first step. Unkeyed failures point at the last step.
- Config `sequence`: `on_invalid` 'jump_to_step', `auto_submit` false, `submit_method` 'submit', `reset_after_submit` false, `continuous` false. Per-sequence fluent overrides.
- `autoSubmit` calls `$livewire->{$method}()` once per valid complete set, after `onCompleted`; a `ValidationException` returns `submitted: false` and skips the reset.
- Events: `ScanAccepted`/`QrCodeScanned`/`ScanRejected` per step with `field` = step key; new `ScanSequenceCompleted`.
- `resources/dist` committed when it changes. Verification: `npm test`, `npm run build`, `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`, example app feature tests against the branch (not committed).

## Files

| File | Responsibility |
|---|---|
| Create `src/Support/ScanStep.php` | step value object |
| Create `src/Support/ScanCheckPipeline.php` | shared rules → lookup → closures pipeline |
| Modify `src/Concerns/InteractsWithScans.php` | delegate to the pipeline |
| Create `src/Concerns/HasSequenceSteps.php` | `steps()`, completion options, `handleStepScan` |
| Create `src/Support/SequenceScanResult.php` | result value object |
| Create `src/Enums/InvalidSequenceBehavior.php`, `src/Events/ScanSequenceCompleted.php` | |
| Modify `src/Forms/Components/QrScanSequence.php` | use the trait; `fields()` adapter; payload |
| Modify `config/qr-code.php`, `resources/lang/{en,id}/ui.php` | `sequence.*`; `step_not_found` |
| Modify `resources/js/scan-pipeline.js`, `resources/js/qr-sequence.js`, sequence Blade | server path, rejection message |
| Tests | `tests/Feature/SequenceStepsTest.php`, `tests/Feature/SequenceScanE2ETest.php`, `resources/js/qr-sequence-steps.test.js`, sequence snapshot |
| Docs | `docs/scanning.md` (sequence section), `docs/reference.md`, CHANGELOG |

---

### Task 1: `ScanStep`, `steps()` and the `fields()` adapter
- [ ] Failing test: "fields(['a','b']) yields the same payload as steps([ScanStep::make('a'), …])" (+ keyed labels, `{key,label}` arrays, closures).
- [ ] Implement `ScanStep` (`make`, `label`, `validateUsing`, `lookup`, `optional`, getters, `toField`, `fromField`), `steps()`, `getScanSteps()`; `getScanFields()` derives from steps. Commit `feat: add ScanStep and QrScanSequence::steps()`.

### Task 2: Extract `ScanCheckPipeline`
- [ ] Failing test: "pipeline runs rules, lookup and closures in order and stops at the first failure" (unit, on the new class).
- [ ] Implement; `InteractsWithScans::runScanCheckStages()` delegates; `HandleScanTest`, `ScanChecksTest` stay green. Commit `refactor: extract ScanCheckPipeline`.

### Task 3: `handleStepScan`, completion and `autoSubmit`
- [ ] Failing E2E test: "invalid step 2 blocks advance; complete valid sequence calls submit once".
- [ ] Implement `handleStepScan`, state writes (own path + prefix path), events, `next_step`, completion with `onCompleted`, `ScanSequenceCompleted`, `autoSubmit`. Commit `feat: check sequence steps on the server`.

### Task 4: Optional auto-skip
- [ ] Failing tests: "optional step is skipped when the code fits the next step", "two optional steps in a row", "optional last step", "both fail rejects with the optional message".
- [ ] Implement. Commit `feat: auto-skip optional sequence steps`.

### Task 5: `validateAllUsing` and `onInvalidSequence`
- [ ] Failing tests: keyed rules point at their step; unkeyed rule and closure point at the last step; closure `[step => message]`; `JumpToStep` keeps other values; `Restart` clears; config fallback.
- [ ] Implement. Commit `feat: validate the whole sequence before completion`.

### Task 6: Reset, `continuous`, submit failure, config, payload
- [ ] Failing tests: `resetAfterSubmit` clears state; `continuous` reports `reset`; failing submit returns `submitted: false` and keeps values; config defaults; payload keys (`serverCheck`, `componentKey`, `steps`, `cooldownMs`, `continuous`); plain `fields()` payload has `serverCheck: false`; snapshot updated for added keys only.
- [ ] Implement. Commit `feat: sequence reset, continuous and config`.

### Task 7: Browser server path
- [ ] Failing Vitest tests: plain sequence keeps `$wire.set` and makes no server call; server sequence calls `handleStepScan` once per scan; pointer moves to `next_step` (skip); rejection keeps pointer and shows message; invalid jumps; completed + reset clears results; duplicate inside cooldown dropped; router refuses while checking.
- [ ] Implement `requestSequenceScan()` and `qr-sequence.js` changes, Blade message line; `npm run build`. Commit `feat: sequence server path in the browser`.

### Task 8: Docs and verification
- [ ] `docs/scanning.md` sequence steps section, `reference.md` config/events, CHANGELOG; full verification; example app feature tests via a local path repo (not committed).
