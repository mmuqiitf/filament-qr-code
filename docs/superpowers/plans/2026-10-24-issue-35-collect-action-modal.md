# Plan: Issue #35

1. Workbench `CollectSmoke` page with `QrCollectAction` header action.
2. Failing tests first (commit): `tests/Browser/collect-action.spec.js`, Vitest `qr-collector-autostart.test.js`.
3. Extract `camera-status` partial; use it in scanner modal, collector field, collector modal.
4. `qrCollector`: `autoStart`, `retryCamera()`, no-device start path.
5. Rebuild dist; Pest, Vitest, Pint, PHPStan, Playwright; CHANGELOG "Fixed" entry.
6. One PR, "Fixes #35". No merge/tag/release, no issue comment.
