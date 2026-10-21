# P2.3 Examples and recipes: design

Date: 2026-10-21 · Roadmap row P2.3 (last P2 item before 0.6.0)

## Goal

Prove the P1, P2.1 and P2.2 public API end to end in the example app, and give users copy-paste recipes that match it.
This needs no new package API. If a recipe can't be written on the public API, that is a bug to report, not a reason to
reach into internals.

Two PRs:

| Repo | Branch | Content |
|---|---|---|
| `mmuqiitf/filament-qr-code` | `docs/p2-3-recipes` | this spec, the plan, recipes, UPGRADE.md, CHANGELOG, a docs test |
| `mmuqiitf/filament-qr-code-example` | `feat/p2-examples` | removes the workarounds, adds the Manufacturing and QR generator pages, the styling showcase, tests, README |

## A. Package

### Recipes (`docs/recipes/`)

Each recipe has a scenario, the full form code, the supporting classes, how it behaves, and a link to the example page
that runs it.

- `pos.md` (update): continuous page-scope scanner. Options follow the server, so no `wire:key` re-mount is needed
  (P2.1). Adds `normalizeWith()` and a pause/resume button through the window events.
- `warehouse.md`: goods receiving. `po` (sticky), `sku` (lookup), `lot` (optional), `serials` (repeat with a closure
  count). `validateAllUsing()`, `autoSubmit()`, `resetAfterSubmit()`, `resetSequence()` after a manual submit, and
  `onStepRejected()` for an audit log.
- `manufacturing.md`: a work-order station. `operator` badge via `lookupUsing()` (non-Eloquent: a badge directory
  service), `work_order` (lookup), `components` (repeat, count from the BOM), `rework_reason` with `when()`. A
  `ScanCheckStage` piped on components ("component belongs to the BOM"), `validateAllUsing()` ("BOM complete"),
  `onStepAccepted()` for an activity feed, and runtime-control buttons (`goToStep`, `resetSequence`, `qr-sequence-pause`).
- `generator-page.md`: a Filament page that previews and downloads QR codes from a payload builder (`QrPayload`,
  the `FilamentQrCode` facade, `QrCodeDisplay`). It lives in the app; no package page or generator command
  (dropped 2026-10-20).
- `custom-input.md`: an RFID or keypad input in the shared hardware router (`window.FilamentQrCode.router.register`),
  feeding a scanner (`el.qrScanner.feed`) or a sequence (`qr-sequence-feed`). Also `make:qr-scan-field` for a reusable
  field class.
- `styling.md`: `extraAttributes()`, `fi-qr-*` classes, `--fi-qr-*` CSS variables, render hooks (scopes, static step
  header), and overriding one published sub-view.

### Docs test

`tests/Feature/RecipesTest.php` checks that:

1. every `php` block in `docs/recipes/*.md` lints (`php -l`, wrapped when it is a fragment), and
2. every `Mmuqiitf\FilamentQrCode\...` class a recipe imports exists, and every `->method(` called on a package
   builder in a recipe exists on at least one package class.

This catches renamed or removed API in docs. It is a smoke check, not a type check.

### UPGRADE.md (0.5 to 0.6)

- Run `php artisan filament:assets`.
- Republish customised views. The scanner, sequence and collector views are split into sub-views and gained classes,
  hooks and `data-qr-options`. A view published from 0.5 keeps working but misses all of that.
- `ScanResult::$record` can hold any value (model, array, object). Use `recordKey()` for the key.
- `normalizeUsing()` now also runs before server checks.
- The route name `filament-qr-code.image` is a deprecated alias for `filamentQrCode.image` and is removed in 1.0.
- New events fields are optional, and `QrCodeScanned` context gains `state_path`.

CHANGELOG gets "Docs" entries under Unreleased and links UPGRADE.md.

## B. Example

- **PosCheckout**: drop the `wire:key="pos-scanner-{{ getOptionsKey() }}"` wrapper and `getOptionsKey()`. The switches
  change options in place.
- **GoodsReceiving**: reorder to `po, sku, lot (optional), serials`, now possible because an optional step gives way to
  a repeat step (P2.1). Replace the hand-written sticky reset after a manual submit with `resetSequence()`. Update the
  two feature tests that assert the old order.
- **Manufacturing** (`/admin/manufacturing`): the work-order station from the recipe. `BadgeDirectory` is a plain
  class with an array of operators; `WorkOrder` and `BomLine` are Eloquent models with seeders;
  `ComponentBelongsToBom` is a stage; there is an activity feed via `onStepAccepted()`; and buttons Restart, Back to
  components and Pause scanning. It shows off styling: `extraAttributes(['class' => 'mfg-station'])`, CSS variables
  in the page theme, and a `SEQUENCE_STEPS_AFTER` render hook scoped to the field.
- **QR generator** (`/admin/qr-generator`): payload type (URL, Wi-Fi, vCard, text), live preview with
  `QrCodeDisplay`, colours and size, and an SVG/PNG download through the facade.
- **Published sub-view**: `resources/views/vendor/filament-qr-code/components/sequence/step-chip.blade.php` (adds an
  icon for sticky steps), used by every sequence in the app.
- **Tests**: feature tests for both pages (incomplete BOM fails `validateAllUsing`, duplicate serial rejected, wrong
  component rejected by the stage, badge lookup, `when()` routing, hook feed, generator download) and a browser test
  for Manufacturing (wedge-driven) and the generator preview.
- **composer**: `"mmuqiitf/filament-qr-code": "dev-main"` with a `TODO(0.6.0)` note in the README and composer
  `extra`, and `minimum-stability: dev` with `prefer-stable: true`. Lock updated with `composer update
  mmuqiitf/filament-qr-code`. Republished assets are committed (the assets CI job checks them).

## Risks

- `dev-main` makes the example's CI depend on package main. That is intended until 0.6.0. The README TODO and the
  release checklist cover switching back.
- Browser tests depend on Playwright. Use a clean `vendor` and kill `playwright run-server` afterwards.
