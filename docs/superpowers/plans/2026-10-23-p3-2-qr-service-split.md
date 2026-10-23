# P3.2 — `QrCodeService` split (plan)

1. Freeze the current service as `tests/Fixtures/LegacyQrCodeService.php`, plus a golden SVG.
2. Characterization tests (`tests/Unit/QrRenderingCharacterizationTest.php`): SVG, PNG,
   logo, caption, logo+caption, cache-key parity, and zip parity. They are written against the new
   classes, so they fail first (classes missing).
3. Extract `HexColor`, `QrRenderOptions`, `QrLogoCompositor`, `QrCaptionCompositor`,
   `QrRenderer`, `QrRenderCache`, `QrZipArchiver`, then rewire `QrCodeService` as a facade
   and turn `QrZipArchive` into a shim. Tests go green.
4. Pin `cache.default=array` in `TestCase` (side fix for the stray Testbench `.env`).
5. Pint, PHPStan (level 9), Pest, Vitest; CHANGELOG "Changed (internal)"; PR; CI green 8.2–8.5.
   No merge, tag or release.
