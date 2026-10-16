# Fix: Outcome Flash Not Visible Implementation Plan

> For agentic workers: superpowers:test-driven-development and superpowers:verification-before-completion. Plan and fix ship in one PR (`fix/flash-feedback`).

**Bug:** on `/admin/server-side-checks` (example app), image upload scans accept/reject correctly but no green/red ring is ever seen on the field.

## Root cause (systematic-debugging, phase 1–3)

1. Assets are not the problem. `resources/dist/filament-qr-code.css` and the example's published `public/css/mmuqiitf/filament-qr-code/filament-qr-code-styles.css` (vendor at 536e74c, dev-main) both contain `.fi-qr-flash-success/-danger` + keyframes, and the published scripts contain `fi-qr-flash`.
2. The class is added, on the right element. `handleScanResult(…, 'upload')` → `applyAccepted/applyRejected` → `playOutcomeFeedback(…, { element: this.$el })`; `this.$el` is the field root (`data-field-name` is read from it in the same method, and the events carry the field name).
3. But it is added **while the scanner modal is still open**. `scanFile()` and `onCameraDecode()` (pass) call `handleScanResult()` first and `closeScannerModal()` after; `closeScannerModal()` only sets `isModalOpen = false` after `await stopScan()`. The modal is a `fixed inset-0 bg-gray-950/70 backdrop-blur-sm` overlay, so the ring plays behind it.
4. The flash is 300 ms and fades from the first frame, so by the time the overlay is gone (stop + Livewire round trip) the class has already been removed. Screenshots at 0.25–2 s can never catch it. On a camera reject the modal stays open, so the field ring is never visible at all.

## Rulings

- **R1** Defer the field flash while the modal is open; run it right after the modal hides. — why: the field is the thing the user looks at after the modal closes — cost if wrong: a flash that starts ~stop latency later (sound/vibration stay immediate).
- **R2** While the modal stays open (camera reject, camera resumes), flash the modal panel instead, and drop the pending field flash. — why: that's what the user is looking at; a stale ring on a later manual close would be misleading — cost if wrong: CSS on one more element.
- **R3** `FLASH_MS` 300 → 900, keyframes hold the ring for the first ~40 % then fade. — why: 300 ms fading from frame 1 is barely perceptible — cost if wrong: one constant.

## Task 1: deferred flash (TDD)

Files: `resources/js/feedback.test.js`, `resources/js/feedback.js`, `resources/js/qr-scanner.js`, `resources/views/components/qr-scanner.blade.php` (`data-qr-scanner-panel` on the panel).

- First failing test: "flashes the field after the scanner modal closes, not behind it" — modal open, slow `stopScan`, reject then `closeScannerModal()`: no class on the field while open; danger class on the field once `isModalOpen` is false; still present at `FLASH_MS - 1`.
- Second: "camera reject with the modal open flashes the panel and leaves no pending field flash".
- Implement: `playOutcomeFeedback` gets the element from `this.flashTargetFor()` = `null` while the modal is open (then `pendingFlash = outcome` and the panel flashes), else `$el`; `closeScannerModal()` runs the pending flash after hiding; `resumeCamera()` clears it.
- Red → green → revert proof (remove the pending-flash call in `closeScannerModal`, test goes red).

## Task 2: CSS, docs, dist

- Keyframes `0%, 40% { ring } to { transparent }`, 900 ms.
- Fix the stale `docs/scanning.md` cooldown sentence ("once the client scan pipeline ships (P1a PR 4)").
- 5 checks, rebuild dist, CHANGELOG Unreleased "Fixed".
