/**
 * Shared camera helpers for all Filament QR Code Alpine components.
 *
 * Keeps the decoder crop (html5-qrcode `qrbox`) and the on-screen reticle
 * in sync, filters symbologies for faster decoding, and remembers the
 * operator's camera choice across visits.
 *
 * The heavy `html5-qrcode` decoder is never statically imported here: camera
 * components resolve it via dynamic `import()` on first use so the global
 * bundle stays light for pages that only render QR images.
 */

const ONE_DIMENSIONAL_FORMATS = new Set([
    'CODABAR',
    'CODE_39',
    'CODE_93',
    'CODE_128',
    'EAN_8',
    'EAN_13',
    'UPC_A',
    'UPC_E',
    'ITF',
]);

const REAR_CAMERA_KEYWORDS = ['back', 'rear', 'environment', 'camera 0', 'facing back', 'main'];

/**
 * Map PHP BarcodeFormat values (or raw strings) to html5-qrcode format ids.
 * Unknown entries pass through untouched so future symbologies keep working.
 * Pass the dynamically-imported Html5QrcodeSupportedFormats enum when
 * available; without it values pass through as strings.
 */
export function mapFormats(formats, formatsEnum = null) {
    if (!Array.isArray(formats) || formats.length === 0) {
        return [];
    }

    return formats.map((format) => {
        if (typeof format !== 'string') {
            return format;
        }

        if (formatsEnum && formatsEnum[format] !== undefined) {
            return formatsEnum[format];
        }

        return format;
    });
}

export function hasOneDimensionalCode(formats) {
    return Array.isArray(formats) && formats.some((format) => ONE_DIMENSIONAL_FORMATS.has(format));
}

/**
 * Compute a responsive decode box for the given viewfinder element.
 *
 * Returns `{ width, height }` so the crop scales with the container instead
 * of using a fixed pixel square. Linear (1D) barcodes get a wide band,
 * which is what makes Code128 / EAN scans reliable without perfect alignment.
 * Both axes are clamped to the measured container so the box (and its
 * reticle) never overflows a 4:3 or otherwise non-square viewfinder.
 */
export function computeQrboxForElement(element, maxBox = 250, formats = []) {
    const containerWidth = element?.clientWidth || 480;
    const containerHeight = element?.clientHeight || 0;
    const wide = hasOneDimensionalCode(formats);
    const target = Math.min(Math.max(maxBox, 120), 600);
    const maxWidth = Math.max(120, Math.min(target, Math.floor(containerWidth * 0.85)));

    if (wide) {
        let height = Math.max(110, Math.floor(maxWidth * 0.55));
        if (containerHeight > 0) {
            height = Math.min(height, Math.floor(containerHeight * 0.8));
        }

        return { width: maxWidth, height: Math.max(80, height) };
    }

    let side = maxWidth;
    if (containerHeight > 0) {
        side = Math.min(side, Math.floor(containerHeight * 0.85));
    }
    side = Math.max(120, side);

    return { width: side, height: side };
}

/**
 * Resize the decorative reticle overlay to match the active decode box.
 *
 * The reticle lives beside (not inside) the decoder host element, so scope
 * the lookup to the shared viewfinder; the library's own shaded overlay is
 * hidden in CSS, leaving exactly one rectangle.
 */
export function syncReticleToQrbox(viewfinderElement, qrbox) {
    if (!viewfinderElement || !qrbox) {
        return;
    }

    const scope = viewfinderElement.closest?.('.filament-qr-viewfinder') || viewfinderElement;
    const box = scope.querySelector('.filament-qr-reticle-box');
    if (!box) {
        return;
    }

    const width = typeof qrbox === 'number' ? qrbox : qrbox.width;
    const height = typeof qrbox === 'number' ? qrbox : (qrbox.height || qrbox.width);

    if (width) {
        box.style.width = `${Math.round(width)}px`;
    }
    if (height) {
        box.style.height = `${Math.round(height)}px`;
    }
}

function readStoredCameraId(storageKey) {
    try {
        return window.localStorage?.getItem(storageKey) || null;
    } catch {
        return null;
    }
}

export function persistCameraId(storageKey, deviceId) {
    try {
        if (storageKey && deviceId) {
            window.localStorage?.setItem(storageKey, deviceId);
        }
    } catch {
        // Private browsing / disabled storage must never break scanning.
    }
}

export function storageKeyFor(scope) {
    return `filament-qr-code:camera:${scope || 'default'}`;
}

/**
 * Pick the camera to start with: remembered choice first, then a rear
 * heuristic, then the first device. Labels are empty until permission is
 * granted, so the stored id is the only reliable signal on first open.
 */
export function selectPreferredCamera(devices, { preferRear = true, storageKey = null } = {}) {
    if (!Array.isArray(devices) || devices.length === 0) {
        return null;
    }

    if (storageKey) {
        const storedId = readStoredCameraId(storageKey);
        if (storedId && devices.some((device) => device.id === storedId)) {
            return storedId;
        }
    }

    if (preferRear) {
        const rear = devices.find((device) => {
            const label = (device.label || '').toLowerCase();
            return REAR_CAMERA_KEYWORDS.some((keyword) => label.includes(keyword));
        });

        if (rear) {
            return rear.id;
        }

        // Plural-camera phones commonly expose rear as the last device.
        if (devices.length > 1 && !devices[0].label) {
            return devices[devices.length - 1].id;
        }
    }

    return devices[0].id;
}

export function triggerFeedback(feedback, { sound = true, vibrate = true, frequency = 880, duration = 80, vibrateDuration = 100 } = {}) {
    feedback?.trigger?.({
        sound,
        vibrate,
        frequency,
        duration,
        vibrateDuration,
    });
}

/**
 * Shared decoder-format config for all camera Alpine modules.
 *
 * Deepens the camera scanning module: the three camera components
 * (QR Scanner Field, Scan Sequence Container, Batch Collector Scanning)
 * previously each carried a verbatim `scannerFormatsConfig()` copy.
 */
export function scannerFormatsConfigFor(formats, formatsEnum = null) {
    const mapped = mapFormats(formats, formatsEnum);
    return mapped.length > 0 ? { formatsToSupport: mapped } : {};
}

/**
 * Resolve the responsive decode box for a viewfinder element id and keep
 * the on-screen reticle in sync. Replaces the per-component `currentQrbox()`.
 */
export function resolveQrboxFor(elementId, maxBox = 250, formats = []) {
    const container = typeof document !== 'undefined' ? document.getElementById(elementId) : null;
    const box = computeQrboxForElement(container, maxBox, formats);
    syncReticleToQrbox(container, box);

    return box;
}

/**
 * Load camera devices into an Alpine component's state.
 *
 * Owns the `isLoading / devices / selectedDeviceId / hasError` triad so
 * callers keep only their empty-device policy (`requireDevices`) and
 * user-facing error strings.
 */
export async function loadCameraDevices(component, {
    Html5QrcodeClass,
    preferRearCamera = true,
    storageKey = null,
    requireDevices = false,
    emptyMessage = 'No camera devices detected on this system.',
    deniedMessage = 'Camera access unavailable.',
    insecureMessage = 'Camera needs a secure context: serve this page over https or open it on localhost, then allow camera access.',
    fixedMessage = false,
} = {}) {
    // Browsers block getUserMedia outside secure contexts with a generic
    // denial — explain the real requirement before the decoder even loads.
    if (typeof window !== 'undefined' && window.isSecureContext === false) {
        component.isLoading = false;
        component.hasError = true;
        component.errorMessage = insecureMessage;

        return;
    }

    component.isLoading = true;
    component.hasError = false;

    try {
        const devices = await Html5QrcodeClass.getCameras();
        component.devices = devices || [];

        if (requireDevices && component.devices.length === 0) {
            throw new Error(emptyMessage);
        }

        if (component.devices.length > 0) {
            component.selectedDeviceId = selectPreferredCamera(component.devices, {
                preferRear: preferRearCamera,
                storageKey,
            });
        }

        component.isLoading = false;
    } catch (err) {
        component.isLoading = false;
        component.hasError = true;
        component.errorMessage = fixedMessage ? deniedMessage : (err?.message || deniedMessage);
    }
}

/**
 * Lazily construct the Html5Qrcode instance for a viewfinder element.
 */
export function ensureScannerInstance(component, elementId, formats, Html5QrcodeClass, formatsEnum = null) {
    if (!component.html5Qrcode) {
        component.html5Qrcode = new Html5QrcodeClass(elementId, scannerFormatsConfigFor(formats, formatsEnum));
    }

    return component.html5Qrcode;
}

/**
 * Decoder resolution. Components receive an html5-qrcode-shaped module
 * (`{ Html5Qrcode, Html5QrcodeSupportedFormats }`). The default loader prefers
 * the browser's native BarcodeDetector when it covers every requested format
 * and falls back to the lazily imported html5-qrcode bundle otherwise.
 * `setDecoder(loader)` swaps the loader; results are cached per format set.
 */
const NATIVE_FORMATS = {
    AZTEC: 'aztec',
    CODABAR: 'codabar',
    CODE_39: 'code_39',
    CODE_93: 'code_93',
    CODE_128: 'code_128',
    DATA_MATRIX: 'data_matrix',
    EAN_8: 'ean_8',
    EAN_13: 'ean_13',
    ITF: 'itf',
    PDF_417: 'pdf417',
    QR_CODE: 'qr_code',
    UPC_A: 'upc_a',
    UPC_E: 'upc_e',
};

export function nativeFormatFor(format) {
    return NATIVE_FORMATS[format] ?? null;
}

let customDecoderLoader = null;
let decoderCache = new Map();

export function setDecoder(loader) {
    if (loader !== null && typeof loader !== 'function') {
        throw new TypeError('setDecoder expects a loader function or null.');
    }

    customDecoderLoader = loader;
    decoderCache = new Map();
}

async function nativeDetectorCovers(formats) {
    const Detector = globalThis.BarcodeDetector;
    if (typeof Detector !== 'function' || typeof Detector.getSupportedFormats !== 'function') {
        return false;
    }

    try {
        const supported = new Set(await Detector.getSupportedFormats());
        const wanted = formats.length > 0 ? formats.map(nativeFormatFor) : ['qr_code'];

        return wanted.every((format) => format !== null && supported.has(format));
    } catch {
        return false;
    }
}

export async function defaultDecoderLoader({ formats = [] } = {}) {
    if (await nativeDetectorCovers(formats)) {
        return createNativeDecoderModule(globalThis.BarcodeDetector);
    }

    return import('html5-qrcode');
}

export async function resolveDecoderModule(formats = []) {
    const list = Array.isArray(formats) ? formats : [];
    const key = [...list].sort().join(',');

    if (!decoderCache.has(key)) {
        const loader = customDecoderLoader ?? defaultDecoderLoader;
        const pending = Promise.resolve(loader({ formats: list }));
        decoderCache.set(key, pending);
        pending.catch(() => decoderCache.delete(key));
    }

    return decoderCache.get(key);
}

/**
 * Native BarcodeDetector adapter exposing the html5-qrcode surface the
 * components use. Detects on the full frame (qrbox is visual only).
 */
export function createNativeDecoderModule(Detector) {
    class NativeScanner {
        constructor(elementId, config = {}) {
            this.elementId = elementId;
            const formats = (config.formatsToSupport ?? []).filter(Boolean);
            this.detector = new Detector(formats.length > 0 ? { formats } : undefined);
            this.stream = null;
            this.video = null;
            this.frame = null;
            this.paused = false;
        }

        static async getCameras() {
            const media = navigator.mediaDevices;
            const probe = await media.getUserMedia({ video: true });
            probe.getTracks().forEach((track) => track.stop());
            const devices = await media.enumerateDevices();

            return devices
                .filter((device) => device.kind === 'videoinput')
                .map((device) => ({ id: device.deviceId, label: device.label }));
        }

        async start(cameraId, config = {}, onSuccess = () => {}, onError = () => {}) {
            const deviceConstraint = typeof cameraId === 'string' ? { deviceId: { exact: cameraId } } : cameraId;
            this.stream = await navigator.mediaDevices.getUserMedia({ video: deviceConstraint, audio: false });

            const video = document.createElement('video');
            video.muted = true;
            video.playsInline = true;
            video.setAttribute('playsinline', '');
            video.style.width = '100%';
            video.srcObject = this.stream;
            document.getElementById(this.elementId)?.appendChild(video);
            this.video = video;
            await video.play();

            const interval = 1000 / Math.max(1, config.fps ?? 10);
            let last = 0;
            let busy = false;
            const tick = async (now) => {
                if (!this.stream) return;
                this.frame = requestAnimationFrame(tick);
                if (this.paused || busy || now - last < interval || video.readyState < 2) return;
                last = now;
                busy = true;
                try {
                    const [hit] = await this.detector.detect(video);
                    if (hit?.rawValue && this.stream && !this.paused) onSuccess(hit.rawValue, hit);
                } catch (error) {
                    onError(error?.message ?? String(error));
                } finally {
                    busy = false;
                }
            };
            this.frame = requestAnimationFrame(tick);
        }

        async stop() {
            if (this.frame !== null) cancelAnimationFrame(this.frame);
            this.frame = null;
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
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
            const bitmap = await createImageBitmap(file);
            try {
                const [hit] = await this.detector.detect(bitmap);
                if (!hit?.rawValue) throw new Error('No barcode found in the image.');

                return hit.rawValue;
            } finally {
                bitmap.close?.();
            }
        }

        runningTrack() {
            const track = this.stream?.getVideoTracks?.()[0];
            if (!track) throw new Error('Camera is not running.');

            return track;
        }

        getRunningTrackCapabilities() {
            return this.runningTrack().getCapabilities?.() ?? {};
        }

        applyVideoConstraints(constraints) {
            return this.runningTrack().applyConstraints(constraints);
        }
    }

    return { Html5Qrcode: NativeScanner, Html5QrcodeSupportedFormats: { ...NATIVE_FORMATS }, native: true };
}

/**
 * Torch / zoom support read from the running camera track. Never throws.
 */
export function cameraCapabilitiesFor(instance) {
    const none = { torch: false, zoom: null };
    if (!instance || typeof instance.getRunningTrackCapabilities !== 'function') return none;

    let caps;
    try {
        caps = instance.getRunningTrackCapabilities() ?? {};
    } catch {
        return none;
    }

    const zoom = caps.zoom;
    const hasZoom = zoom && typeof zoom.min === 'number' && typeof zoom.max === 'number' && zoom.max > zoom.min;

    return {
        torch: caps.torch === true || (Array.isArray(caps.torch) && caps.torch.includes(true)),
        zoom: hasZoom ? { min: zoom.min, max: zoom.max, step: zoom.step || 0.1 } : null,
    };
}

async function applyAdvanced(instance, constraint) {
    if (!instance || typeof instance.applyVideoConstraints !== 'function') return false;
    try {
        await instance.applyVideoConstraints({ advanced: [constraint] });
        return true;
    } catch {
        return false;
    }
}

export function applyTorch(instance, on) {
    return applyAdvanced(instance, { torch: Boolean(on) });
}

export function applyZoom(instance, value) {
    return applyAdvanced(instance, { zoom: Number(value) });
}

/**
 * Alpine mixin state shared by the camera components.
 */
export function refreshCameraControls(component, enabled = component.options?.cameraControls !== false) {
    const caps = !enabled
        ? { torch: false, zoom: null }
        : cameraCapabilitiesFor(component.html5Qrcode);
    component.cameraCaps = caps;
    component.torchOn = false;
    component.zoomLevel = caps.zoom ? caps.zoom.min : 1;
}

export function resetCameraControls(component) {
    component.cameraCaps = { torch: false, zoom: null };
    component.torchOn = false;
}

export function cameraControlsMixin() {
    return {
        cameraCaps: { torch: false, zoom: null },
        torchOn: false,
        zoomLevel: 1,

        async toggleTorch() {
            const next = !this.torchOn;
            if (await applyTorch(this.html5Qrcode, next)) this.torchOn = next;
        },

        async setZoom(value) {
            if (await applyZoom(this.html5Qrcode, value)) this.zoomLevel = Number(value);
        },
    };
}

/**
 * Shared camera stop: every camera module guards on the same
 * `html5Qrcode && isScanning` seam and clears `isScanning` in `finally`.
 */
export async function stopCameraFeed(component, logLabel = 'scanner') {
    // Cancel any start still in flight: it checks the run when it resolves.
    component.cameraRun = (component.cameraRun ?? 0) + 1;
    // An explicit stop wins over a pending resume (see watchPageVisibility).
    component.cameraPausedByVisibility = false;

    if (component.html5Qrcode && component.isScanning) {
        try {
            await component.html5Qrcode.stop();
        } catch (e) {
            console.debug(`Error stopping ${logLabel}:`, e);
        } finally {
            component.isScanning = false;
        }
    }

    resetCameraControls(component);
}

/**
 * Remap Alpine feedback options onto the Scan Feedback module's seam.
 * Replaces the identical `triggerFeedback(qrFeedback, {...})` block
 * previously copied into every decode handler.
 */
export function emitScanFeedback(feedback, {
    sound = true,
    vibrate = true,
    beepFrequency = 880,
    beepDurationMs = 80,
    vibrateDurationMs = 100,
} = {}) {
    triggerFeedback(feedback, {
        sound,
        vibrate,
        frequency: beepFrequency,
        duration: beepDurationMs,
        vibrateDuration: vibrateDurationMs,
    });
}

/**
 * The current camera run. Every stop bumps it, so a caller that awaited
 * device loading can tell the camera was closed in the meantime.
 */
export function currentCameraRun(component) {
    return component.cameraRun ?? 0;
}

/**
 * Shared camera start. html5Qrcode.start() takes a second or two on a real
 * camera; when the modal closes (or Livewire removes the component) during
 * that window the stop is a no-op, so a start that resolves for a cancelled
 * run stops its own stream instead of leaving the camera on. Pass the run
 * captured before any earlier await to also cancel across device loading.
 */
export async function startCameraFeed(component, start, run = currentCameraRun(component)) {
    if (currentCameraRun(component) !== run) {
        return false;
    }

    // Started while the page is hidden: wait for it to be visible again.
    if (typeof document !== 'undefined' && document.hidden) {
        component.cameraPausedByVisibility = true;

        return false;
    }

    component.cameraStarting = true;

    try {
        await start();
    } finally {
        component.cameraStarting = false;
    }

    if (currentCameraRun(component) !== run) {
        try {
            await component.html5Qrcode?.stop();
        } catch (e) {
            console.debug('Error stopping a cancelled camera start:', e);
        }

        return false;
    }

    component.isScanning = true;

    return true;
}

/**
 * Stop the camera while the page is hidden (a locked phone, a background
 * tab) and start it again once the page is visible. `stop` bumps the camera
 * run, so a start still in flight stops its own stream when it resolves.
 * An explicit stop in between clears the pause, so a camera the user closed
 * stays closed. Returns the cleanup function.
 */
export function watchPageVisibility(component, { stop, resume }) {
    if (typeof document === 'undefined') {
        return () => {};
    }

    const onChange = () => {
        if (document.hidden) {
            if (component.isScanning || component.cameraStarting) {
                stop();
                component.cameraPausedByVisibility = true;
            }

            return;
        }

        if (component.cameraPausedByVisibility) {
            component.cameraPausedByVisibility = false;
            resume();
        }
    };

    document.addEventListener('visibilitychange', onChange);

    return () => document.removeEventListener('visibilitychange', onChange);
}
