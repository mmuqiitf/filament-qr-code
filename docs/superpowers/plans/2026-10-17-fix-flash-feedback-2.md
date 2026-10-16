# Fix: Outcome Flash Lands on the File Input Implementation Plan

> superpowers:systematic-debugging, test-driven-development, verification-before-completion. Plan and fix in one PR (`fix/flash-feedback-2`).

**Bug:** after #23, the example app (`/admin/server-side-checks`, package main a4ae4a5) still shows no ring on the SKU field after an image upload.

## Evidence
- The served assets are current. The published `filament-qr-code-scripts.js` contains `pendingFlash`, `flashTargetFor` and `[data-qr-scanner-panel]`, and the published CSS has the `fi-qr-flash-*` keyframes. The page payload has `successFeedback = {sound: true, vibrate: [100], flash: true}`.
- A Pest browser test in the example put a MutationObserver on `document.body` and uploaded `sku-valid-PRD-1001.png`. The `class` mutations it recorded at +204 ms were `INPUT.hidden fi-qr-flash-success`, and nothing changed on the field root. `qr-scan-accepted` carried `field: null`.

## Root cause
In Alpine 3, `this.$el` inside a component method is **the element whose directive called the method**, not the `x-data` root. `scanFile()` runs from `@change` on the hidden `<input type="file">`, so `this.$el` is that input:
- The flash class went onto a hidden input.
- `this.$el.querySelector('[data-qr-scanner-panel]')` returned null.
- `data-field-name` read as null, so the events have had `field: null` on the upload path.

The Vitest harness pins `$el` to the root, which hid the problem.

## Rulings
- **R1:** add a `fieldElement()` helper that returns `this.$root ?? this.$el` (Alpine's `$root` is the closest `x-data` element), and use it everywhere `this.$el` was used in `qr-scanner.js`. Why: correct no matter which directive called the method. Cost if wrong: one helper.
- **R2:** the harness gains `$root` and an optional `currentElement` that simulates `$el` being the calling element. Why: the tests can then catch this class of bug. Cost if wrong: test-only.
- **R3:** the example keeps its browser test, which asserts the ring on the field wrapper within 1 s, as a regression guard. Why: the upload path is only fully real in a browser. Cost if wrong: about 3 s of CI time.

## Task 1 (TDD)
- First failing test (`feedback.test.js`): "upload from the file input flashes the field root and names the field". Mount with `currentElement` set to the file input, open the modal, upload-path accept, then close. Assert the root gets `fi-qr-flash-success` and `qr-scan-accepted.detail.field === 'sku'`.
- Implement R1 and R2, get to green, then a revert proof (`fieldElement()` returns `this.$el`, so the test goes red).

## Task 2
- Run the 5 checks, rebuild dist, add a CHANGELOG "Fixed" entry, open the PR and get CI green. Then bump the example and keep its browser test.
