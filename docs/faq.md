# FAQ

> Back to [README](../README.md) · [Getting started](getting-started.md) · [Scanning](scanning.md) · [Reference](reference.md)

Each answer below describes what the package code does. For symptoms and fixes, see the [Troubleshooting table](../README.md#troubleshooting).

## Why is there no torch (flashlight) button on my iPhone?

The torch and zoom controls only appear when the running camera track reports that capability (`getCapabilities()` returns `torch` or `zoom`). The package never guesses. If the browser doesn't report torch support, which is common on iOS Safari, the button stays hidden and the rest of the scanner works as usual. `->cameraControls(false)` (or `camera.controls => false`) hides the controls everywhere.

## Why does the camera say it needs a secure context?

Browsers only allow camera access on secure origins: `https`, or `localhost` during development. When `window.isSecureContext` is false, the scanner shows the `camera_needs_secure_context` message instead of asking for permission, and it never loads the decoder. Serve the panel over `https`. For testing on a phone over the LAN, use an `https` tunnel or a trusted local certificate. The image-upload fallback, typing and Hardware Scanners still work over plain `http`.

## Does scanning work offline?

The browser side has no external dependency. The scanner bundle and the camera decoder are compiled into `resources/dist` and served from your app by `php artisan filament:assets`, with no CDN. When the browser has a native `BarcodeDetector` that covers every requested format, it is used, and otherwise the bundled `html5-qrcode` decoder loads. The decoder still has to be downloaded from your server once. Scanned values go into the Livewire state, and anything server-side needs a connection: lookups, `validateUsing()`, `onScanned()`, events and form submit. The package has no offline queue.

## Do I need special drivers for a handheld gun?

No, as long as the gun is in keyboard (HID / keyboard-wedge) mode. The Hardware Scanner Interceptor tells a gun from a person by timing:

- the keystrokes arrive within `burst_threshold_ms` (50 ms) of each other;
- there are at least `min_fast_keys` (3) fast gaps in a row;
- the code is at least `min_barcode_length` (2) characters long;
- a terminator key (`Enter` or `Tab`) ends the scan, or, for guns without one, `scan_timeout_ms` passes.

The terminator is swallowed so the form doesn't submit. Slow Bluetooth guns can lower `->minFastKeys(1)`. Guns in serial (SPP / COM port) mode don't type keystrokes, so the package doesn't see them. See [`hardware_scanner`](reference.md#hardware_scanner).

## A gun scan lands in the wrong field. Who gets the burst?

One page-wide router hands every burst to exactly one owner. The order is: the open camera modal, then the focused scan field, then the first listening target on the page, then the Station Listener's first-empty-input fallback, and finally a `qr-scan-unrouted` event. See [Hardware routing](reference.md#hardware-routing). To keep a search box out of the fallback, use `->fallbackToAnyInput(false)`. To keep the router's hands off an input entirely, add `data-qr-ignore`.

## Does it work with several panels?

Yes. The service provider registers the JS and CSS once for every panel, so `->plugin(FilamentQrCodePlugin::make())` is optional on each one. Register it on a panel only if you call `FilamentQrCodePlugin::get()` there. Each panel theme needs its own `@source` line for the package views ([Getting started, step 3](getting-started.md#3-add-the-package-views-to-your-theme)). Config is app-wide. To vary a default per panel, use component setters or `configureUsing()`.
