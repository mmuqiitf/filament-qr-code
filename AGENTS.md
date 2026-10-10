# AGENTS.md

Filament v5 QR package (`mmuqiitf/filament-qr-code`): camera / hardware scanning + QR generation for Laravel 11–13, PHP `^8.2`.

## Commands

- `composer test` — full suite (Pest + Testbench); single test: `vendor/bin/pest --filter="..."`.
- `composer analyse` — PHPStan level 9 (`src/` + `config/`); `composer format` — Pint fix, `composer test-style` to check.
- `npm run build` after any `resources/js|css` change and commit `resources/dist/`; host apps then run `php artisan filament:assets`.

## Pointers (read the branch you are working)

- Scanning (camera field, station listener, sequence, focus handoff, POS tutorial) → `docs/scanning.md`.
- Collecting (batch collector, collect action) → `docs/collecting.md`.
- Generating (display/column/entry components, download actions, facade, payloads) → `docs/generating.md`.
- Tuning (config, events, customizing, extending) → `docs/reference.md`; stuck users → `README.md` Troubleshooting.
- Contributing (tests, style, frontend builds) → `CONTRIBUTING.md`.
- Vocabulary (Station Listener, Scan Sequence Container, Batch Collector, …) → `CONTEXT.md`; use those names for APIs/docs, UI strings under `filament-qr-code::ui.*`.
- Decisions (Filament exclusivity, bundled decoder, interceptor, symbologies) → `docs/adr/`.

## Invariants (survive refactors; CI handles the mechanical)

- Keep camera DOM in `wire:ignore` islands: every `$wire.set` morphs and destroys `<video>`, so sync live toggles via window events, not re-renders.
- Keep one scan funnel per surface: one `$wire` method per scan source; field burst handlers stand down while a page-global listener is mounted unless forced with `->suppressWhenGlobalListener(false)`.
- Treat `scanFormat()` / `onScan()` / `onStepScanned()` as programmatic-only (pair with `formatScannedValue()` / `triggerOnScan()`); normalize live values in `normalizeUsing()` / `normalizeStepUsing()` (or `afterStateUpdated()`) and document them that way.
- Bind sequences through the schema's `statePath()` and read state via `mergeSequenceState()` / `isSequenceComplete()`, not hand-rolled merges.
- Filter `formats()` on every camera component; `qrbox` is a responsive maximum and `fps` auto-degrades unless set explicitly.
- Serve one visible scan rectangle (custom reticle; the decoder library overlay stays hidden) and never hand-edit `resources/dist/`.
- Keep new PHP under `src/` + `config/` typed to level 9 with `declare(strict_types=1)`; PNG/text-overlay paths fall back to GD bitmap fonts on missing fonts instead of throwing.
