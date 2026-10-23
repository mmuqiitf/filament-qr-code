# P3.2 — `QrCodeService` split (design)

Roadmap row (Q5): `QrCodeService.php` is ~600 LOC mixing fluent configuration,
encoding, two-level caching, logo/caption compositing and hex parsing. Split it
into focused collaborators. Pure internal refactor: no behaviour or public API change.

## Target shape

| Class | Responsibility |
|---|---|
| `Services\QrCodeService` | Unchanged fluent API and static cache toggles; a thin facade that builds `QrRenderOptions`, asks `QrRenderCache`, and delegates to `QrRenderer`. |
| `Rendering\QrRenderOptions` | Immutable value object with every render input and the cache key (`md5` of the same field list, same order). |
| `Rendering\QrRenderer` | Encodes SVG (Bacon SVG backend) or PNG (Imagick backend, GD fallback), then applies logo and caption. |
| `Rendering\QrLogoCompositor` | Centers a logo over PNG bytes (Imagick, GD fallback). |
| `Rendering\QrCaptionCompositor` | Adds the caption strip under PNG bytes (Imagick, GD fallback). |
| `Rendering\QrRenderCache` | L1 in-process (200-entry FIFO) + L2 Laravel Cache, with the same key prefix `filament-qr-code:render:`, TTL rules and best-effort error swallowing. |
| `Rendering\HexColor` | `#rgb`/`#rrggbb` parsing with the same `InvalidArgumentException` message. |
| `Rendering\QrZipArchiver` | ZIP packing (moved from `Support\QrZipArchive`, which stays as a delegating shim). |

Public static methods (`persistentCache`, `cacheTtl`, `cacheStore`,
`effectiveCacheTtl`, `flushRenderCache`, `flushPersistentCache`) stay on
`QrCodeService` and forward to `QrRenderCache`.

## Characterization (tests first)

- `QrCodeServiceTest` stays green untouched.
- A frozen copy of the pre-refactor service lives in `tests/Fixtures/LegacyQrCodeService.php`.
  New tests assert byte-for-byte equality between legacy and new output for SVG,
  PNG, logo, caption, and logo+caption. This compares against whatever backend
  the runner has (Imagick in CI, GD locally), so it does not need per-environment goldens.
- "QrRenderer renders same SVG bytes" also compares against a committed golden
  SVG (SVG output does not depend on the environment).
- ZIP: new archiver bytes equal the legacy `QrZipArchive` bytes, with the DOS
  mtime/mdate header fields zeroed, because ZipArchive stamps the current time.

## Side fix

Running the Playwright workbench (`workbench:build`) writes a `.env`
(`CACHE_STORE=database`) into the Testbench skeleton, which Pest then loads.
`TestCase` now pins `cache.default = array`, so Pest no longer depends on that file.

## Out of scope

Any change to the render output, the cache key format, or the facade surface.
