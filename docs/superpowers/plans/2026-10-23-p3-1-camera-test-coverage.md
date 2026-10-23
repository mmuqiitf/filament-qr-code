# P3.1 — Camera and test coverage (plan)

1. Vitest first (red): `uses BarcodeDetector when available, else html5-qrcode`,
   `setDecoder` override + reset + per-format cache, native adapter start/detect/stop,
   torch/zoom capability detection and constraint application.
2. Implement `setDecoder`, default loader, native adapter, capability helpers in
   `qr-camera-core.js` (green). Pass formats to `resolveDecoderModule` in
   scanner, sequence, collector.
3. Pest first (red): `cameraControls()` option defaults to config, appears in the
   payload for scanner, sequence, collector and `QrCollectAction`.
4. Implement PHP option + config key; Blade `camera-controls` partial in all
   viewfinders; component state/methods. Update view snapshots.
5. Recipe `docs/recipes/zxing-decoder.md` (passes `RecipesTest`), docs/scanning,
   CHANGELOG Unreleased.
6. Workbench + Playwright smoke tests (fake camera decode, 8 ms wedge burst →
   value + success flash) + `.github/workflows/browser-tests.yml`.
7. Rebuild dist; run Pest, Vitest, Pint, PHPStan, Playwright; open PR; CI green on
   PHP 8.2–8.5. No merge/tag/release.
