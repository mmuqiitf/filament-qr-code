# P2.1 Runtime control: implementation plan

Spec: `docs/superpowers/specs/2026-10-20-p2-1-runtime-control-design.md`.
Strict TDD: write the failing test, watch it fail, write the minimum code to pass, refactor.
Style: `.agents/skills/spatie-laravel-php`, `.agents/skills/spatie-javascript`.

## Tasks

1. **`make()` runs `setUp()`** (PHP)
   - Pest: `QrScanSequence::make()` applies `configureUsing()` and a `default([])` fill.
   - Code: `$static->configure()` in `make()`.
2. **`OptionsSync` and `syncOptions()`** (PHP)
   - Pest: the payload has `syncOptions` = config `scan.sync_options` (default
     `morph`); a per-field override and closure win; an invalid value falls back to
     `morph`.
   - Code: enum, a `HasOptionsSync` concern on QrScanner and QrScanSequence, the config
     key.
3. **`data-qr-options` rendered** (Blade)
   - Pest: the scanner root carries `data-qr-scanner` and `data-qr-options`; the
     sequence wrapper carries `data-qr-options` outside the `wire:ignore` element.
4. **Scanner runtime options** (JS)
   - Vitest: "setOptions switches mode without re-creating the scanner"; the observer
     applies a changed attribute in morph mode and ignores it in refresh mode until
     `qr-scanner-refresh`; an option change during a check applies after it; a formats
     change rebuilds the decoder on the next start; hardware options re-register the
     router target.
   - Code: `options` object, `applyOptions`, `pendingOptions`, a MutationObserver
     (disconnected in `destroy()`).
5. **Scanner control API** (JS)
   - Vitest: `el.qrScanner.feed / pause / resume / reset / getState`; window events
     targeted by state path, field name and key; untargeted events reach all; other
     fields ignore targeted ones.
   - Code: a shared `control-api.js` helper (target matching, listener lifecycle).
6. **Sequence payload: `values`, `stepState`, `when()`** (PHP)
   - Pest: "changing quantity updates repeat k/n" (payload); `when()` false skips the
     step in `findNextStep`, is not required for completion, rejects direct scans, and
     drops its value; a throwing condition counts as active.
7. **Optional skip into repeat steps** (PHP)
   - Pest: "optional lot skips into serials" (po, sku, lot?, serials); the skipped step
     is reported; a full repeat step still rejects.
8. **Page-level sequence methods** (PHP)
   - Pest: `goToStep` dispatches `qr-sequence-go-to` (an unknown step throws
     `InvalidArgumentException`); `resetSequence` writes sticky values and dispatches;
     `setRepeatCount` changes `getRepeatLimit`, persists across requests, and `null`
     clears it.
9. **Sequence runtime options and control API** (JS)
   - Vitest: "changing quantity updates repeat k/n"; values replaced from the payload
     when they differ; a structure change is deferred mid-round; nothing applies during a
     check; `feed / goToStep / reset / pause / resume` per element and via events;
     `qr-sequence-set-repeat`.
10. **Public router** (JS)
    - Vitest: `window.FilamentQrCode.router` is the shared `inputRouter()`; a custom
      target registered through it receives a burst.
11. **Docs and CHANGELOG**
    - `docs/scanning.md` (runtime control, sequence order and `when()`, page methods);
      `docs/reference.md` (events tables, config `sync_options`, `sequence` section,
      router); `CHANGELOG.md` Unreleased.
12. **Verification**
    - `npm test`, `npm run build` (commit dist), Pest, Pint, PHPStan.
    - Example app against the branch: feature and browser suites; PosCheckout without
      `wire:key`; GoodsReceiving counter and lot-after-SKU order. Not committed. Restore
      composer files and kill `playwright run-server`.
