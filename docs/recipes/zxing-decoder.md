# Recipe: use ZXing as the camera decoder

The camera decoder is swappable. By default the package uses the browser's native
`BarcodeDetector` when it supports every format the field asks for (Chrome on Android,
macOS and ChromeOS, Edge) and falls back to the bundled html5-qrcode otherwise. If you
would rather decode everything with [ZXing](https://github.com/zxing-js/browser), drop
in the adapter below. ZXing is not bundled; you install it in your own app.

```bash
npm install @zxing/browser @zxing/library
```

## The adapter

A decoder is a module with the html5-qrcode surface the scanner, sequence and collector
use. Save this as `resources/js/zxing-decoder.js`:

```js
import { BrowserCodeReader, BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

class ZxingScanner {
    constructor(elementId, { formatsToSupport = [] } = {}) {
        this.elementId = elementId;
        const hints = new Map();
        if (formatsToSupport.length > 0) {
            hints.set(DecodeHintType.POSSIBLE_FORMATS, formatsToSupport);
        }
        this.reader = new BrowserMultiFormatReader(hints);
        this.controls = null;
        this.video = null;
        this.paused = false;
    }

    static async getCameras() {
        const devices = await BrowserCodeReader.listVideoInputDevices();

        return devices.map((device) => ({ id: device.deviceId, label: device.label }));
    }

    async start(cameraId, config, onSuccess) {
        this.video = document.createElement('video');
        this.video.muted = true;
        this.video.playsInline = true;
        this.video.style.width = '100%';
        document.getElementById(this.elementId).appendChild(this.video);

        this.controls = await this.reader.decodeFromVideoDevice(cameraId, this.video, (result) => {
            if (result && !this.paused) {
                onSuccess(result.getText(), result);
            }
        });
    }

    async stop() {
        this.controls?.stop();
        this.controls = null;
        this.video?.remove();
        this.video = null;
    }

    pause() {
        this.paused = true;
    }

    resume() {
        this.paused = false;
    }

    async scanFile(file) {
        const url = URL.createObjectURL(file);
        try {
            return (await this.reader.decodeFromImageUrl(url)).getText();
        } finally {
            URL.revokeObjectURL(url);
        }
    }

    // Optional: lets the built-in torch / zoom buttons work.
    getRunningTrackCapabilities() {
        return this.video.srcObject.getVideoTracks()[0].getCapabilities();
    }

    applyVideoConstraints(constraints) {
        return this.video.srcObject.getVideoTracks()[0].applyConstraints(constraints);
    }
}

export default { Html5Qrcode: ZxingScanner, Html5QrcodeSupportedFormats: BarcodeFormat };
```

`Html5QrcodeSupportedFormats` maps the PHP `BarcodeFormat` names (`QR_CODE`, `CODE_128`,
`EAN_13`, …) to the decoder's own ids. ZXing's `BarcodeFormat` enum uses the same names, so
it can be passed straight through.

## Register it

Load it once, before anyone opens a camera. A Filament render hook in your panel provider
works well, or import it from your app bundle:

```js
import zxing from './zxing-decoder.js';

window.FilamentQrCode.qrCameraCore.setDecoder(() => zxing);
```

The loader receives `{ formats }` (the field's PHP format names), so you can mix decoders,
for example ZXing only for `MAXICODE` and the default everywhere else:

```js
const { qrCameraCore } = window.FilamentQrCode;

qrCameraCore.setDecoder(async ({ formats }) =>
    formats.includes('MAXICODE') ? (await import('./zxing-decoder.js')).default : qrCameraCore.defaultDecoderLoader({ formats }),
);
```

Call `setDecoder(null)` to go back to the default.
