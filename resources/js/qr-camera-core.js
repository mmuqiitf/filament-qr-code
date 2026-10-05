import { Html5QrcodeSupportedFormats } from 'html5-qrcode';

/**
 * Shared camera helpers for all Filament QR Code Alpine components.
 *
 * Keeps the decoder crop (html5-qrcode `qrbox`) and the on-screen reticle
 * in sync, filters symbologies for faster decoding, and remembers the
 * operator's camera choice across visits.
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
 */
export function mapFormats(formats) {
    if (!Array.isArray(formats) || formats.length === 0) {
        return [];
    }

    return formats.map((format) => {
        if (typeof format !== 'string') {
            return format;
        }

        return Html5QrcodeSupportedFormats[format] !== undefined
            ? Html5QrcodeSupportedFormats[format]
            : format;
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
 */
export function syncReticleToQrbox(viewfinderElement, qrbox) {
    if (!viewfinderElement || !qrbox) {
        return;
    }

    const box = viewfinderElement.querySelector('.filament-qr-reticle-box');
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

export function getZoomState(html5Qrcode) {
    try {
        const capabilities = html5Qrcode?.getRunningTrackCameraCapabilities?.();
        const zoom = capabilities?.zoomFeature?.();

        if (!zoom || !zoom.isSupported()) {
            return null;
        }

        return {
            min: zoom.getMin?.() ?? 1,
            max: zoom.getMax?.() ?? 5,
            step: 0.1,
        };
    } catch {
        return null;
    }
}

export async function applyZoomLevel(html5Qrcode, value) {
    try {
        const capabilities = html5Qrcode?.getRunningTrackCameraCapabilities?.();
        const zoom = capabilities?.zoomFeature?.();

        if (zoom && zoom.isSupported()) {
            await zoom.apply(value);
            return true;
        }
    } catch (e) {
        console.debug('Zoom apply error:', e);
    }

    return false;
}

export function hasTorchSupport(html5Qrcode) {
    try {
        const capabilities = html5Qrcode?.getRunningTrackCameraCapabilities?.();
        return !!(capabilities && capabilities.torchFeature?.().isSupported());
    } catch {
        return false;
    }
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
