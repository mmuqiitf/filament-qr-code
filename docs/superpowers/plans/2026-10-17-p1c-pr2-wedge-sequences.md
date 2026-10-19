# P1c PR B: Wedge-Driven Sequences, Sticky and Repeat Steps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A hardware scanner fills and advances sequence steps without focus, `sticky()` steps survive reset and continuous rounds, and `repeat(n)` steps collect several codes with a configurable completion (auto-advance by default, or wait for a finish scan or the Done control; open-ended repeats always wait).

**Architecture:** Server-side in `HasSequenceSteps`: sticky values are kept by every reset path, repeat steps store `list<string>`, a finish code or the exposed `finishStep(step)` ends waiting or open-ended steps. Browser-side `qr-sequence.js` keeps following the server's `next_step`; the router target gains `accept()` (refuse while checking), `hold()` (queue one burst) and focused-step delivery (`data-qr-step`). The step list shows `k / n` and a Done control.

**Tech Stack:** Filament v5 schema components, `#[ExposedLivewireMethod]`, Pest 4 + Livewire testing, Vitest + happy-dom.

**Spec:** `docs/superpowers/specs/2026-10-17-p1c-sequences-design.md` section 7, row B of section 11.

## Global Constraints

- PHP: `.agents/skills/spatie-laravel-php`; JS: `.agents/skills/spatie-javascript`.
- Existing users unaffected: no sticky/repeat step means identical payload values (payload only gains keys), no new request, same events.
- Sticky or repeat steps count as server work (`handleStepScan` per scan).
- Repeat completion (decision 3): config `sequence.repeat_complete` (`'advance'` default, or `'wait'`), per step `onRepeatComplete(RepeatComplete)`. `repeat()` with no count is open-ended and always waits.
- Finish: step `finishWith()` code, else config `sequence.finish_code` (default `null`), or `finishStep(step)` from the Done control. Finish codes are never stored.
- Optional skip stays as decided (decision 1).
- Rebuild and commit `resources/dist`. Verify with Vitest, Pest, Pint, PHPStan and the example app's feature and browser tests against the branch (not committed).

## Files

| File | Responsibility |
|---|---|
| Modify `src/Support/ScanStep.php` | `sticky()`, `repeat()`, `onRepeatComplete()`, `finishWith()`, `allowDuplicates()` |
| Create `src/Enums/RepeatComplete.php` | `Advance`, `Wait` |
| Modify `src/Concerns/HasSequenceSteps.php` | sticky resets, repeat lists, finish, filled checks |
| Modify `src/Forms/Components/QrScanSequence.php` | payload `steps`, normalised state for lists |
| Modify `config/qr-code.php`, `resources/lang/{en,id}/ui.php` | `repeat_complete`, `finish_code`; `repeat_full`, `duplicate_in_step`, `repeat_needs_codes`, `done` |
| Modify `resources/js/qr-sequence.js`, sequence Blade | router accept/hold, focused step, `k / n`, Done |
| Tests | `tests/Feature/SequenceWedgeTest.php`, `resources/js/qr-sequence-wedge.test.js`, snapshot |
| Docs | `docs/scanning.md`, CHANGELOG |

---

### Task 1: Sticky steps and wedge filling (first failing tests)
- [ ] Failing Vitest: "three bursts fill steps po, sku, lot in order; sticky po survives reset": three wedge bursts with nothing focused reach `handleStepScan` for `po`, `sku`, `lot` in order; after a `reset` response, `results` keep `po` and the pointer goes to `sku`.
- [ ] Failing Pest (same name): three `handleStepScan` calls fill `po`, `sku`, `lot` with `continuous()`; after completion `values === ['po' => 'PO-1']` and `next_step === 'sku'`. Plus sticky across `resetAfterSubmit` and `Restart`; sticky makes the sequence server work.
- [ ] Implement `ScanStep::sticky()`, sticky-aware reset (`stickyValues()`), first empty step after reset. Commit.

### Task 2: Router ownership in the browser
- [ ] Failing Vitest: a burst with a step input focused goes to that step; `accept()` is false while a check is in flight and one burst is held and replayed; plain sequences still deliver locally.
- [ ] Implement `data-qr-step` on step inputs, `accept`/`hold` on the target, `source` in `qr-sequence-step`. Commit.

### Task 3: `repeat(n)` with auto-advance
- [ ] Failing Pest: `repeat(3)` collects a list, `k < n` stays on the step, `k === n` advances; duplicates rejected with `duplicate_in_step` unless `allowDuplicates()`; each code runs the step checks; `repeat(fn (array $values) => …)`.
- [ ] Implement. Commit.

### Task 4: Wait, open-ended and finish
- [ ] Failing Pest: config `repeat_complete = wait` keeps the pointer on a full step and rejects more with `repeat_full`; per-step `onRepeatComplete(Advance)` overrides it; open-ended `repeat()` waits; finish code (step and config) finishes without storing; `finishStep()` from the Done control; finishing an empty step is rejected; finishing the last step completes and submits.
- [ ] Implement. Commit.

### Task 5: Payload, Blade and JS for repeats
- [ ] Failing Vitest: list values render as `k / n`; Done calls `finishStep`; payload `steps` drives it.
- [ ] Failing Pest: payload `steps` keys; snapshot gains keys only; submit rules validate each repeat code.
- [ ] Implement; rebuild dist. Commit.

### Task 6: Docs and verification
- [ ] `docs/scanning.md`, CHANGELOG. Full verification and example app feature + browser tests against the branch; kill leftover `playwright run-server`; restore composer files.

### Task 7: Resolved decisions (PR #32 review)
- [ ] Failing Pest: a throwing count closure falls back to open-ended and logs a local warning, then counts once values exist; `removeStepCode` removes one code; `clearStep` clears a sticky step and returns `next_step` = that step; a step `finishWith()` overrides config `finish_code`.
- [ ] Failing Vitest: `removeCode()` and `clearStep()` call the server, resync `results`, move the pointer, and fire no step events; `isSticky()`.
- [ ] Implement the server methods, JS actions, Blade remove chips and Clear button, `clear`/`remove_code` strings; rebuild dist. Docs, CHANGELOG, full verification. Commit.

