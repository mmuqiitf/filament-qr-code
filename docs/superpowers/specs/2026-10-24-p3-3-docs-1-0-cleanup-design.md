# P3.3: Docs and 1.0 cleanup

Roadmap row P3.3 (`filament-qr-code-plan.md`) plus three extras the owner approved on 2026-10-24.
Target release: 1.0.0 (not tagged here; the owner's go-ahead and an example-app run come first).

## 1. Remove the deprecated APIs

| Removed | Replacement | Notes |
| --- | --- | --- |
| Route name `filament-qr-code.image` | `filamentQrCode.image` | The `{legacy?}` alias route goes. |
| `suppressWhenGlobalListener()`, `isSuppressedWhenGlobalListenerActive()` | none needed | The input router gives every burst one owner since 0.5. The `suppressWhenGlobalListenerActive` scan payload key, the collect action's `suppressWhenGlobal` key, the JS handler option and the JS export `isGlobalHardwareListenerActive()` go too. |
| `QrScanSequence::statePathPrefix()`, `getStatePathPrefix()`, `prefixMismatchWarning()` | none needed | The flat per-step paths are now always the form's own state path (`$this->getContainer()->getStatePath()`), the value `statePathPrefix()` had to match. The mismatch warning (and `ui.sequence_prefix_mismatch`) can no longer happen, so it goes. A sequence with `statePath()` still writes both its own path and the flat paths. |
| `Listeners\LogQrCodeScan` | `Listeners\LogQrCodeScanListener` | Spatie listener naming. No alias. |
| `hardware_scanner.min_fast_keys` default `1` | `3` | A burst needs 3 consecutive fast gaps. The JS fallback defaults move to 3 as well. Apps that scan very short codes (under 4 characters) set it back with `->hardwareScanner(minFastKeys: 1)` or config. |

Not removed (open question for the owner): `QrCodeDisplay::getQrSvgOrDataUri()`, also marked deprecated but not on the P3.3 list.

UPGRADE.md gets a "From 0.6 to 1.0" section with one numbered entry per row. The Pest test "removed APIs are gone; UPGRADE.md lists each" checks that every symbol in a list is gone from `src/`, `config/`, `resources/js` (non-test), `resources/views` and docs, and that UPGRADE.md names each one.

## 2. Behaviour fixes found in P2.3

**Whole-set check on early finish of `repeat(n)`.** Finishing a counted repeat step early (the Done button or the finish code) is rejected with `repeat_needs_codes`, and `validateAllUsing()` never runs, so an app's own "BOM short: missing X, Y" message can't show. Now `finishRepeatStep()` runs the whole-set check first when the step is short and `validateAllUsing()` is set: a failure is reported like a failed completion (message, invalid step, `qr-sequence-invalid`, `onInvalidSequence()` behaviour). When it passes, the step is still short, so `repeat_needs_codes` applies as before. Pest: "whole-set check runs on early finish of repeat(n)".

**Stages see the same codes live and on submit.** Live, `checkStep()` gets the values before the scan (a repeat step holds the codes already in). On submit, `SequenceStepRule` passes the full state, including the code being checked, the rest of its repeat list and the later steps. A stage such as "no more than 2 of part X" or "component not already used" then passes live and fails on submit. Now a stage (and the step's closures) sees one shape everywhere: the active steps before this one, plus, for a repeat step, the codes before this one. Live checks of a non-repeat step drop the step's own old value too. Pest: "stage sees the same codes live and on submit".

## 3. Docs

- `docs/events.md`: the complete events reference. Every browser event dispatched (detail shape, when), every control event a page can send, and every Laravel event (properties). Pest guards completeness: every `new CustomEvent('qr-…')` name in `resources/js` and every class in `src/Events` must appear.
- `docs/reference.md`: the full `sequence` config table (all keys) and links to the events page.
- `docs/extending.md`: the extension-points page. One table of every seam (PHP closures, contracts, macros, `configureUsing`, render hooks, views, CSS variables, JS decoder, router, events) with its signature and the page that shows it in use.
- README: a documentation table (page → what it covers) replacing the "Full guides" list. Troubleshooting rows that named removed APIs are updated.
- Doc-snippet test (Pest): every ```php block in README.md, UPGRADE.md and `docs/**` (not `docs/superpowers`) must parse with nikic/php-parser, either as is or wrapped as a fragment (statement, chain, or class member). Every `use Mmuqiitf\FilamentQrCode\…` import must resolve to a class, interface or enum.

## 4. Extras

1. **CI triggers.** All workflows also run on pushes and PRs to the maintenance branches `0.*.x` and `1.*.x`.
2. **Scanner modal inside an action modal or slide-over.** The scanner modal is `position: fixed`. An ancestor with a `transform` (Filament's modal window and slide-over panel) becomes its containing block, so the "full screen" overlay is clipped to the action modal. Playwright test on a workbench page with a `QrScanner` inside an action modal and a slide-over: the open scanner modal must cover the viewport. If confirmed, the fix is `x-teleport="body"` on the modal template.

   **Finding (2026-10-24):** not confirmed on Filament 4. With the overlay positioned, its box equals the viewport inside both the action modal and the slide-over, with or without a teleport. The red test did show a misplaced overlay (x 216, y 205, 848×1544), but the cause was different: the overlay's `fixed inset-0 z-50` are Tailwind utilities, which exist only when the panel theme `@source`s the package views. Without that the overlay is a plain block inside the action modal. Fix: the package stylesheet now positions `.fi-qr-scanner-modal` itself (fixed, inset 0, z-index 50). No teleport, so the DOM and Alpine scope stay as they are.
3. **Pause the camera while the page is hidden.** `watchPageVisibility(component, { stop, resume })` in qr-camera-core is used by the scanner, sequence and collector:
   - Hidden while scanning or mid-start: stop (bumps the camera run, so a start in flight stops its own stream), and remember the pause.
   - Visible again: resume once.
   - A start that begins while the page is hidden is deferred the same way.
   - An explicit stop (closing the modal, Stop) clears the remembered pause, so a closed modal never reopens the camera.

   Vitest first.

## 5. Pre-1.0 polish (approved 2026-10-25)

1. **Translate camera errors.** Every user-facing string in `resources/js` comes from `filament-qr-code::ui.*`, passed in the scan payload like `insecureMessage`:
   - New keys: `camera_decoder_failed`, `camera_unavailable`, `camera_none_found`, `camera_start_failed`, `camera_device` (the `Camera :id` fallback label in the device pickers). en + id.
   - The JS fallbacks live in one module, `resources/js/messages.js`, used only when a component mounts without a payload (tests, custom wiring).
   - `loadCameraDevices()` shows the translated message instead of the raw browser error text (the raw error goes to `console.debug`).
   - The unused `ui.loading_camera` key and the duplicate `done` key are removed.
   - Pest guard (`TranslationsTest`): every `ui.*` key used in `src/` and `resources/views` exists in every lang file; every lang file has the same keys; `messages.js` fallbacks equal the en strings; no JS source outside `messages.js` assigns a sentence literal to a message sink (`errorMessage =`, `…Message:` / `…Message =` defaults, `|| '…'`).
2. **Screen-reader announcements.** A visually hidden polite live region (`role="status"`, `aria-live="polite"`, `aria-atomic="true"`, partial `components/announcer`) in the collector field, the collect-action modal and the sequence:
   - Collector: accepted `Added :code (:count items)`; a duplicate is `Rejected: Duplicate codes are not allowed.`
   - Sequence: accepted `Added :code to :step`, `Sequence complete.` at the end; rejected `Rejected: :reason`.
   - No duplicates: an identical announcement within 3 s is skipped (a code held under the camera is not repeated every frame); an identical one later is cleared and re-set so it is read again. The visible sequence error box drops `role="alert"` (the live region speaks it once); camera errors keep `role="alert"` (assertive is for errors only).
   - Remove buttons in the collector lists get `aria-label` (`ui.remove_code`).
   - Tests: vitest (`announcer.test.js`, collector and sequence wiring), Pest view test (live region markup), Playwright assertion in `collect-action.spec.js`.
3. **CI Laravel matrix.** `run-tests.yml` runs Laravel 11/12/13 × prefer-lowest/prefer-stable × PHP 8.2–8.5 (Spatie skeleton pattern: `composer require "laravel/framework:<v>" "orchestra/testbench:<v>" --no-update`, then `composer update --<dependency-version>`). Laravel 11 → testbench 9, Laravel 12 → testbench 10, Laravel 13 → testbench 11 (Pest 4, PHP 8.3+). PHP 8.2 × Laravel 13 is excluded. `composer.json` dev constraints widen to testbench `^11`, Pest `^4`; any minimum raised to make prefer-lowest pass is listed in CHANGELOG and UPGRADE.

## Out of scope

No layout or behaviour changes beyond the above. No tag or release.
