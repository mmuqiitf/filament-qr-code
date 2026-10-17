# P1b PR B: Continuous Mode Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** `QrScanner::mode(ScanMode::Continuous)` for POS scan-scan-scan: every accepted code runs `onScanned()` once (one `handleScan` call per scan), the field is ready again straight away, rejected scans show the error and keep scanning (or pause until dismissed), the camera closes after each scan (or stays open), and bursts that arrive while a check is in flight are held in a one-slot queue.

**Architecture:** PHP: two enums, a `HasScanMode` trait on `QrScanner` (fluent setters with `scan.continuous.*` config fallbacks), and three small hooks in `InteractsWithScans` (continuous forces the server call, uses its own cooldown default, and empties state after `onScanned()`). JS: `qr-scanner.js` reads `mode`, `continuousDisplay`, `pauseOnReject`, `keepCameraOpen` from the payload; the router (from PR A) gains a `hold()` step so a busy target can queue a burst only after every other target declined. Blade gets an inline rejection message and a paused banner with a resume button.

**Tech Stack:** Filament v5, Livewire 3, Pest 4, Vitest 5 + happy-dom, Alpine component objects, Vite.

**Spec:** `docs/superpowers/specs/2026-10-17-p1b-input-router-continuous-mode-design.md` sections 2.3 (decisions 2–4), 4.4 (`qr-scan-paused`, `qr-scan-resumed`, `qr-scan-dropped`, `mode`), 4.5, 5.1–5.4, 6, 7 (rapid bursts, cooldown), 9 (continuous bullets), 10, 12 (resolutions 2, 3, 5).

## Global Constraints

- PHP: `.agents/skills/spatie-laravel-php`. JS: `.agents/skills/spatie-javascript`.
- Defaults (config `scan.continuous`): `display => 'empty'`, `pause_on_reject => false`, `keep_camera_open => false`, `cooldown_ms => 300`. Normal fields keep `scan.cooldown_ms` (1500). `cooldown()` overrides both.
- Single mode is the default and changes nothing: no new server call, same payload values for existing keys, same events (scanner payload snapshot gains only the new keys).
- Continuous forces `hasServerScanChecks()` to true, so each accepted code is one `handleScan` call and `onScanned()` runs there exactly once.
- `acceptScan()` order in continuous mode: `state(code)` → `afterStateUpdated` → `onScanned()` → events → `state(null)` when display is `Empty` (no second `afterStateUpdated`).
- Router step order is unchanged; the new `hold` step runs only after fallback and before `qr-scan-unrouted`: targets that refused because they were busy (`accept()` false) are offered `hold(code, context)` in routing order; the first `true` wins and `qr-scan-routed` fires with `reason: 'queued'`.
- One-slot queue: a continuous field holds at most one code while checking; any further code is dropped with `qr-scan-dropped` `{ value, field, source }`. The held code is replayed after the check, unless the field paused, in which case it is dropped the same way.
- `pauseOnReject`: the field refuses bursts (and does not hold them) until resumed; camera stays paused; `qr-scan-paused` / `qr-scan-resumed` `{ field }`.
- `required()` on a continuous field: one `Log::warning` per field name in the local environment; never throws.
- Events gain `mode: 'continuous'` for continuous fields.
- `resources/dist` committed when it changes. Verification: `npm test`, `npm run build`, `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`.

## Files

| File | Responsibility |
|---|---|
| Create `src/Enums/ScanMode.php`, `src/Enums/ContinuousDisplay.php` | enums |
| Create `src/Concerns/HasScanMode.php` | `mode()`, `continuousDisplay()`, `pauseOnReject()`, `keepCameraOpen()`, getters, payload, `required()` warning |
| Modify `src/Concerns/InteractsWithScans.php` | continuous hooks in `hasServerScanChecks()`, `getCooldownMs()`, `acceptScan()` |
| Modify `src/Forms/Components/QrScanner.php` | use `HasScanMode`; merge its payload |
| Modify `config/qr-code.php` | `scan.continuous` |
| Modify `resources/js/input-router.js` | `hold` step |
| Modify `resources/js/qr-scanner.js` | continuous accept/reject/camera/queue/pause |
| Modify `resources/views/components/qr-scanner.blade.php`, `resources/lang/{en,id}/ui.php` | rejection line, paused banner |
| Tests | `tests/Feature/ContinuousModeTest.php`, `resources/js/qr-scanner-continuous.test.js`, router tests, scanner snapshot |
| Docs | `docs/scanning.md`, `docs/reference.md`, `docs/recipes/pos.md`, `CHANGELOG.md` |

---

### Task 1: Server side of continuous mode

- [ ] **Step 1: Failing test** (Pest `ContinuousModeTest`): "continuous mode keeps state empty and calls onScanned per accepted code" — three `handleScan` calls on a continuous field with `onScanned` recording `$code` → recorded `['A-1','A-2','A-3']`, state `null` after each, each response `passes: true`.
- [ ] **Step 2:** run, FAIL (`mode()` undefined).
- [ ] **Step 3:** enums, `HasScanMode`, hooks in `InteractsWithScans`, config.
- [ ] **Step 4:** PASS. Add: "last_code display keeps the code", "continuous without checks still goes through handleScan" (`hasServerScanChecks()` true), "afterStateUpdated runs once per scan", "events fire per code", "cooldown defaults to scan.continuous.cooldown_ms (300); cooldown() wins; single fields keep 1500", "config fallbacks and invalid display → empty", "payload exposes mode/continuousDisplay/pauseOnReject/keepCameraOpen", "required() logs one warning in local env and never throws", "single mode payload values unchanged". Update the scanner snapshot (added keys only).
- [ ] **Step 5:** commit `feat: add continuous scan mode on the server`.

### Task 2: Router `hold` step

- [ ] **Step 1: Failing tests:** "offers a busy target the burst only after every other target declined", "a held burst dispatches qr-scan-routed with reason queued", "unrouted when the busy target does not hold".
- [ ] **Step 2–4:** implement; PASS. **Step 5:** commit `feat: let busy router targets hold a burst`.

### Task 3: Continuous mode in the browser

- [ ] **Step 1: Failing tests** (`qr-scanner-continuous.test.js`): "continuous mode keeps state empty and calls onScanned per accepted code" (client half: three accepted results → `value === ''` each time, three `handleScan` calls, three `qr-scan-accepted` with `mode: 'continuous'`); "last_code display keeps the code"; "reject shows the message and keeps scanning"; "pauseOnReject refuses bursts until resumed and emits qr-scan-paused/qr-scan-resumed"; "camera modal closes after each accepted scan by default"; "keepCameraOpen resumes the camera instead"; "a burst during a check is queued and replayed"; "a second burst during a check is dropped with qr-scan-dropped"; "a queued code is dropped when the field pauses".
- [ ] **Step 2–4:** implement; Blade rejection line + paused banner + translations. PASS.
- [ ] **Step 5:** `npm run build`, commit with dist: `feat: continuous scan mode in the browser`.

### Task 4: Docs

- [ ] `docs/scanning.md` section, `docs/reference.md` config + events, new `docs/recipes/pos.md` (continuous + page scope + lookup + merging cart lines in `onScanned()`), CHANGELOG. Full verification. Commit `docs: document continuous mode and POS recipe`.

### Task 5: Example app (separate repo, branch `feat/pos-checkout`, no PR, constraint stays `^0.4`)

- [ ] Failing Pest test "same SKU twice gives quantity 2" against a new `PosCheckout` page (`/pos-checkout`, route name `posCheckout`), then the page: continuous `QrScanner` with `hardwareInput(scope: Page)` + `lookup(Product::class, 'sku')`, `onScanned` merges cart lines, live toggles for `continuousDisplay`, `pauseOnReject`, `keepCameraOpen`. Test locally via an uncommitted path repository.
