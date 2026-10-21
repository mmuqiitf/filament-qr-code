# P2.2 Extension points: implementation plan

Spec: `docs/superpowers/specs/2026-10-20-p2-2-extension-points-design.md`.
Strict TDD: write the failing test, watch it fail, write the minimum code to pass, refactor.
Skills: spatie-laravel-php, spatie-javascript, spatie-security.

## Tasks

0. **Baseline snapshot.** Before any view change, record the rendered HTML of a scanner, a sequence and a
   collector (Pest snapshot). The later output-equivalence test compares against it.
1. **Pipeline: lookups and stages** (`ScanCheckPipeline`, `ScanCheckStage`, `ScanCheckContext`, `ScanResult`)
   - Pest: resolver replaces the model lookup; fallback runs after a miss only; `null`/`false` rejects with
     `record_not_found`; non-model record keys; stages run after closures in order; `false`/string rejections;
     container DI; an exception becomes `scan_failed`.
2. **QrScanner API.** `lookupUsing`, `lookup` closures, `pipe`, `normalizeUsing` before checks, `normalizeWith`.
   - Pest: "lookupUsing resolves from a closure and feeds onScanned"; normalize before rules; presets on the
     server; presets in the payload; submit re-runs the stages; a bad stage class throws.
3. **ScanStep API and injection.** `lookupUsing`, `pipe`, `normalizeUsing`, Macroable, an evaluator from the
   sequence.
   - Pest: "step closure receives `$get`"; `$values` and `$step`; no recursion when a label reads `$values`;
     a step lookup resolver; step stages; macros.
4. **Live step hooks and events.**
   - Pest: `onStepAccepted` / `onStepRejected` fire on live scans with step, result and values; a throwing hook
     doesn't change the result; `ScanAccepted` / `ScanRejected` / `ScanSequenceCompleted` carry the state path
     and Livewire class and id; `QrCodeScanned` has `state_path`.
5. **Browser presets.** `normalizeCode()` in `scan-pipeline.js`, used by the scanner (before dedupe) and the
   sequence.
   - Vitest: the preset order; the scanner dedupes the cleaned code and sends it; the sequence matches it.
6. **Views.** Sub-views, `extraAttributes`, `fi-qr-*` classes, render hooks (`QrCodeRenderHook`), CSS variables.
   - Pest: output equivalent to the baseline; `extraAttributes` on the roots; classes present; "render hook
     content appears in the step header"; hook scopes (Livewire class, state path).
7. **Generator.** `make:qr-scan-field`, stubs, publish tag.
   - Pest: snapshot of the generated `SkuScanner.php`; `--sequence`; refuse to overwrite without `--force`; a
     published stub wins.
8. **Docs and CHANGELOG.** A new "Extending" section in `docs/reference.md`, a `docs/customizing.md` for views,
   and the CHANGELOG under Unreleased.
9. **Verification.**
   - `npm test`, `npm run build` (commit dist), Pest, Pint, PHPStan.
   - Example app feature and browser suites against the branch, with the package folder linked. Restore it, kill
     `playwright run-server`, and commit nothing to the example.
