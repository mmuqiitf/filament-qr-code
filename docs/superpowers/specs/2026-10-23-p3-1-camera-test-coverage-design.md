# P3.1 — Camera and test coverage (design)

Roadmap row: swappable decoder (`qrCameraCore.setDecoder(loader)`), native
`BarcodeDetector` with html5-qrcode fallback, sample ZXing adapter, torch/zoom;
package-level browser smoke tests in CI (synthetic wedge + fake camera).

## Decisions (final, from brainstorm)

1. The decoder is swappable with `FilamentQrCode.qrCameraCore.setDecoder(loader)`.
   The default loader uses native `BarcodeDetector` when available and it
   supports every requested format, otherwise it falls back to html5-qrcode.
2. Torch and zoom controls appear automatically when the running camera track
   reports the capability. `->cameraControls(false)` (or config
   `qr-code.camera.controls`) hides them per field.
3. A ZXing adapter ships as a recipe (`docs/recipes/zxing-decoder.md`) with
   copy-paste code; it is not bundled and adds no dependency.
4. Playwright smoke tests run in CI against a Testbench workbench app:
   fake camera stream (canvas `captureStream`) plus a synthetic 8 ms
   keyboard-wedge burst that fills the field and shows the success flash.

## Decoder contract

The three camera components keep talking to an html5-qrcode-shaped module:

```
{ Html5Qrcode, Html5QrcodeSupportedFormats }
Html5Qrcode.getCameras(): Promise<{id, label}[]>
new Html5Qrcode(elementId, { formatsToSupport })
  .start(cameraId, { fps, qrbox }, onSuccess(text), onError)
  .stop() .pause(true) .resume() .scanFile(file, false)
  .getRunningTrackCapabilities() .applyVideoConstraints(constraints)  // optional
```

`setDecoder(loader)`: `loader` is `({ formats }) => module | Promise<module>`.
`setDecoder(null)` restores the default. Results are cached per format set and
the cache is cleared on every `setDecoder` call. `resolveDecoderModule(formats)`
now takes the field formats.

Default loader: `globalThis.BarcodeDetector` present and
`BarcodeDetector.getSupportedFormats()` covers the mapped formats (empty
formats → requires `qr_code`) → `createNativeDecoderModule()`; else
`import('html5-qrcode')`. Any error in native probing falls back.

Native adapter: `getUserMedia({ video: { deviceId } })` into a muted inline
`<video>` appended to the element, a `requestAnimationFrame` loop throttled to
`fps` that calls `detector.detect(video)`, `scanFile` via
`createImageBitmap(file)`. Format map: PHP `QR_CODE` → `qr_code`, `CODE_128` →
`code_128`, `PDF_417` → `pdf417`, etc.

## Torch / zoom

Core helpers: `cameraCapabilitiesFor(instance)` → `{ torch, zoom: {min,max,step} | null }`
(never throws), `applyTorch(instance, on)`, `applyZoom(instance, value)` via
`applyVideoConstraints({ advanced: [{ … }] })`. Components call
`refreshCameraControls()` after `start()` and reset on stop. A shared Blade
partial `camera-controls` renders a torch toggle and zoom range inside each
viewfinder, shown only when `options.cameraControls` and the capability exist.

## Browser smoke tests

`workbench/` (Testbench) with a Filament panel without auth and a page holding a
`QrScanner` field. `@playwright/test` runs against `vendor/bin/testbench serve`.
Fake camera: init script replacing `enumerateDevices`/`getUserMedia` with a canvas
stream drawing a QR. Headless Chromium on Linux has no `BarcodeDetector`, so CI
exercises the html5-qrcode path; native path is covered by Vitest.

## Out of scope / limits

- iOS Safari exposes no torch capability; controls just stay hidden.
- Native adapter ignores `qrbox` cropping (detects the whole frame).
