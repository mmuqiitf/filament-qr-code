# AGENTS.md

`mmuqiitf/filament-qr-code` adds camera and hardware scanning and QR and barcode generation to Filament v5 apps. See [composer.json](composer.json) for supported PHP and Laravel versions.

## Architecture and coding

- Reuse [existing extension points](docs/extending.md) before adding new ones. Preserve documented APIs across refactors and mark implementation-only public members `@internal` per the [public API policy](docs/reference.md#public-api-and-internal).
- Use [project vocabulary](CONTEXT.md) in APIs and docs. Put UI strings under `filament-qr-code::ui.*`.
- Keep PHP in `src/` and `config/` typed to PHPStan level 9 with `declare(strict_types=1)`.
- Fall back to GD bitmap fonts when fonts are missing on PNG and text-overlay paths.

## Verification and generated files

- Only add unit or integration tests that are worth it: protect real behaviour, public API contracts, regressions, or tricky edge cases (such as rejected scans, duplicate input, or missing fonts). Avoid low-value tests — trivial getters/setters, re-testing framework or vendor behaviour, duplicate coverage, or snapshot noise — that only add lines of code. Prefer extending an existing test over creating a new file; TDD still applies to meaningful behaviour, using the simplest setup that asserts observable outcomes.
- Follow [CONTRIBUTING.md](CONTRIBUTING.md) for test, analysis, and formatting commands, including frontend tests with `npm test`.
- Run `composer docs` after changing public signatures, public docblocks, config keys, or user-facing docs, including `README.md` and `UPGRADE.md`. It regenerates `docs/api.md` and `llms-full.txt`. For new docs pages, update `llms.txt` and check whether the page needs an explicit entry in `llmsFullFiles()` in [the generator](bin/generate-api-docs.php).
- After changes under `resources/js/` or `resources/css/`, regenerate `resources/dist/` with `npm run build` and include the generated changes with the source. Host apps refresh the assets with `php artisan filament:assets`.

## Task guides

- **Scanning** → [Camera field, Station Listener, scan sequences, and focus handoff](docs/scanning.md).
- **Collecting** → [Batch Collector and Collect Action](docs/collecting.md).
- **Generating** → [Display components, downloads, facade, and payloads](docs/generating.md).
- **Config** → [Configuration reference](docs/reference.md#configuration).
- **Customization** → [Normalization, auditing, styling, and hooks](docs/customizing.md).
- **Events** → [Browser controls and Laravel events](docs/events.md).
- **Troubleshooting** → [README](README.md#troubleshooting).
- **Host app integration** → Start with the [AI guide](docs/ai.md). Review it and the [Laravel Boost guidance](resources/boost/) for examples affected by public API changes.

## Scanning invariants

- Keep camera DOM inside `wire:ignore`. Let Livewire morph `data-qr-options` outside the ignored camera DOM and use the [documented window events](docs/events.md#browser-events-you-send) for imperative controls.
- Keep one scan funnel per surface and one `$wire` method per scan source. Route keyboard-wedge bursts through `FilamentQrCode.router` so each burst has a single owner.
- Keep `scanFormat()`, `onScan()`, and `onStepScanned()` programmatic. Call `formatScannedValue()` for formatting, `triggerOnScan()` for the field callback, and `triggerOnStep()` for sequence steps. Use `normalizeUsing()`, `normalizeStepUsing()`, or a field's `afterStateUpdated()` for live normalization.
- Bind sequences through the schema's `statePath()`. Read sequence state with `mergeSequenceState()` and check completion with `isSequenceComplete()`.
- Apply `formats()` filtering on every camera component. Treat `qrbox` as a responsive maximum and let `fps` auto-degrade unless explicitly set.
- Show the custom reticle as the single scan rectangle and keep the decoder overlay hidden.
