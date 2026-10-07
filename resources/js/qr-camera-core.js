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
    fixedMessage = false,
} = {}) {
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
 * Resolve the heavy html5-qrcode module on demand. The result is cached per
 * page load so the 300K+ decoder downloads exactly once, on first camera use.
 */
let cachedDecoderModule = null;

export async function resolveDecoderModule() {
    if (cachedDecoderModule) {
        return cachedDecoderModule;
    }

    cachedDecoderModule = await import('html5-qrcode');

    return cachedDecoderModule;
}

/**
 * Shared camera stop: every camera module guards on the same
 * `html5Qrcode && isScanning` seam and clears `isScanning` in `finally`.
 */
export async function stopCameraFeed(component, logLabel = 'scanner') {
    if (component.html5Qrcode && component.isScanning) {
        try {
            await component.html5Qrcode.stop();
        } catch (e) {
            console.debug(`Error stopping ${logLabel}:`, e);
        } finally {
            component.isScanning = false;
        }
    }
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
