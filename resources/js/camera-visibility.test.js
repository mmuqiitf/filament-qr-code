// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import qrCollectorComponent from './qr-collector.js';
import qrScanSequenceComponent from './qr-sequence.js';
import { mountComponent } from './alpine-test-harness.js';

// A phone that locks or a tab sent to the background keeps the camera on
// (battery, privacy LED). The camera now stops while the page is hidden
// and starts again when it is visible.
const camera = vi.hoisted(() => ({ starts: 0, pending: null, instant: true, stop: null }));

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: async () => ({
        Html5Qrcode: { getCameras: async () => [{ id: 'cam-1', label: 'Back camera' }] },
        Html5QrcodeSupportedFormats: {},
    }),
    ensureScannerInstance: (component) => {
        component.html5Qrcode = {
            start: () => {
                camera.starts++;
                if (camera.instant) return Promise.resolve();
                return new Promise((resolve) => { camera.pending = resolve; });
            },
            stop: camera.stop,
            clear: () => {},
        };
    },
    refreshCameraControls: () => {},
}));

const flush = async () => {
    for (let i = 0; i < 10; i++) await new Promise((resolve) => setTimeout(resolve, 0));
};

let hidden = false;
let mounted = [];

const track = (c) => {
    mounted.push(c);
    return c;
};

const setHidden = async (value) => {
    hidden = value;
    document.dispatchEvent(new Event('visibilitychange'));
    await flush();
};

const element = () => document.body.appendChild(document.createElement('div'));

const subjects = {
    qrScanner: async () => {
        const c = track(mountComponent(qrScannerComponent({}), { element: element() }));
        c.openScannerModal();
        await flush();
        return { c, close: () => c.closeScannerModal() };
    },
    qrCollector: async () => {
        const c = track(mountComponent(
            qrCollectorComponent({ sound: false, vibrate: false, hardwareScanner: false, autoStart: true }),
            { element: element() },
        ));
        await flush();
        return { c, close: () => c.stopCollector() };
    },
    qrScanSequence: async () => {
        const c = track(mountComponent(qrScanSequenceComponent({ fields: [{ key: 'a', label: 'A' }] }), { element: element() }));
        await flush();
        c.startScanner();
        await flush();
        return { c, close: () => c.stopScanner() };
    },
};

describe.each(Object.keys(subjects))('%s pauses the camera while the page is hidden', (name) => {
    beforeEach(() => {
        hidden = false;
        Object.defineProperty(document, 'hidden', { configurable: true, get: () => hidden });
        Object.assign(camera, { starts: 0, pending: null, instant: true, stop: vi.fn(async () => {}) });
    });

    afterEach(async () => {
        mounted.forEach((c) => c.destroy());
        mounted = [];
        hidden = false;
        await flush();
    });

    it('stops when hidden and starts again when visible', async () => {
        const { c } = await subjects[name]();
        expect(c.isScanning).toBe(true);
        expect(camera.starts).toBe(1);

        await setHidden(true);
        expect(camera.stop).toHaveBeenCalled();
        expect(c.isScanning).toBe(false);

        await setHidden(false);
        expect(camera.starts).toBe(2);
        expect(c.isScanning).toBe(true);
    });

    it('does not start a camera the user stopped while hidden', async () => {
        const { c, close } = await subjects[name]();
        await setHidden(true);
        await close();
        await flush();

        await setHidden(false);
        expect(camera.starts).toBe(1);
        expect(c.isScanning).toBe(false);
    });

    it('does not start a camera that was off when the page was hidden', async () => {
        const { c, close } = await subjects[name]();
        await close();
        await flush();

        await setHidden(true);
        await setHidden(false);
        expect(camera.starts).toBe(1);
        expect(c.isScanning).toBe(false);
    });

    it('hidden mid-start stops the stream once it opens, then resumes on visible', async () => {
        camera.instant = false;
        const { c } = await subjects[name]();
        expect(camera.pending).toBeTypeOf('function');

        await setHidden(true);
        camera.pending();
        await flush();
        expect(camera.stop).toHaveBeenCalled();
        expect(c.isScanning).toBe(false);

        camera.instant = true;
        await setHidden(false);
        expect(camera.starts).toBe(2);
        expect(c.isScanning).toBe(true);
    });

    it('stops listening after destroy', async () => {
        const { c } = await subjects[name]();
        c.destroy();
        await flush();

        await setHidden(true);
        await setHidden(false);
        expect(camera.starts).toBe(1);
    });
});
