# Issue #35: QrCollectAction modal parity with QrScanner

## Report

"QrCollectAction does not work. When opening the modal, it not only has a different display
pattern compared to the QrScanner, but it also fails to load the camera view." (mbitelo, 2026-10-20, v0.5.x)

## Reproduction (workbench `/workbench/collect-smoke`, Playwright fake camera)

- Happy path (camera granted instantly, user clicks Start) works on v0.5.0, v0.6.0 and main.
- Denied / unavailable camera: `qrCollector.hasError = true`, `errorMessage = 'Camera access unavailable.'`,
  but the modal renders nothing for `isLoading` / `hasError`, so the user sees a black viewfinder.
- Permission still pending: `loadCameras()` runs in `init()`, `selectedDeviceId` is `null` until the
  prompt resolves, and `startCollector()` returns silently, so Start does nothing and the camera never shows.

## Root cause

1. Camera view: the collector (modal and field) never surfaces the loading/error state that
   `loadCameraDevices()` already maintains, and Start is a silent no-op until a device is selected.
   The scanner modal shows a spinner, the error and a Retry button, and starts the camera on open.
2. Display pattern: `qr-collector-modal.blade.php` is a hand-rolled copy of the viewfinder without
   the scanner's status overlays and without auto-start, so the two modals behave and look different.

3. Found while testing (likely the main "does not work"): `handleDetectedCode()` checks
   `typeof this.$wire.handleCollectorScan === 'function'`. Livewire's `$wire` is a proxy that returns a
   callable for every name, so the check is always true: every scan calls a missing Livewire method
   (`MethodNotFoundException`, 500 error modal) and `qr-collector-item-added` never fires. Present since the
   first release. The server now sends `wireScanHandler` (`method_exists` on the host component).
4. Auto-start in an opening modal can measure a 0px-wide container, so html5-qrcode renders a zero-sized
   video. `startCollector()` waits (bounded, 2 s) for the viewfinder to get a width.

## Design

- One shared partial `components/camera-status.blade.php` (spinner + error + Retry) included by the
  scanner modal, the collector field and the collector modal. Retry action is a parameter.
- `qrCollector` gains `autoStart` (default `false`, the field keeps its explicit Start) and `retryCamera()`.
  The action modal passes `autoStart: true`, mirroring `QrScanner::openScannerModal()`.
- `startCollector()` with no device selected reloads cameras and starts if one appears, else shows the error.
- No PHP API change. Not a breaking change.

## Tests

- Browser: modal auto-starts and collects without clicking; denied camera shows the message and Retry.
- Vitest: `autoStart` starts after devices load; no start when off; `retryCamera()` clears the error and starts.
