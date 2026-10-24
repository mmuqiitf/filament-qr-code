// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import qrScanSequenceComponent from './qr-sequence.js';
import { mountComponent } from './alpine-test-harness.js';

// Same hazards #35 fixed in the collector: Start was a silent no-op before
// devices loaded, and camera errors were set but never shown or retryable.
const camera = vi.hoisted(() => ({ cameras: [], start: null }));

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: async () => ({
        Html5Qrcode: { getCameras: async () => camera.cameras },
        Html5QrcodeSupportedFormats: {},
    }),
    ensureScannerInstance: (component) => {
        component.html5Qrcode = { start: camera.start, stop: async () => {}, clear: () => {} };
    },
    refreshCameraControls: () => {},
}));

const flush = async () => {
    for (let i = 0; i < 5; i++) await new Promise((resolve) => setTimeout(resolve, 0));
};

const mount = () => mountComponent(
    qrScanSequenceComponent({ fields: [{ key: 'a', label: 'A' }], sound: false, vibrate: false, hardwareScanner: false }),
    { element: document.body.appendChild(document.createElement('div')) },
);

describe('qrScanSequence camera start', () => {
    beforeEach(() => {
        camera.cameras = [{ id: 'cam-1', label: 'Back camera' }];
        camera.start = vi.fn(async () => {});
    });

    it('loads cameras on demand when Start is pressed before devices resolved', async () => {
        const sequence = mount();
        sequence.selectedDeviceId = null;
        await sequence.startScanner();
        await flush();

        expect(camera.start).toHaveBeenCalled();
        expect(sequence.isScanning).toBe(true);
    });

    it('shows an error instead of doing nothing when no camera exists', async () => {
        camera.cameras = [];
        const sequence = mount();
        await flush();
        await sequence.startScanner();

        expect(camera.start).not.toHaveBeenCalled();
        expect(sequence.hasError).toBe(true);
        expect(sequence.errorMessage).not.toBe('');
    });

    it('retryCamera clears the error and starts', async () => {
        camera.cameras = [];
        const sequence = mount();
        await flush();
        await sequence.startScanner();
        expect(sequence.hasError).toBe(true);

        camera.cameras = [{ id: 'cam-1', label: 'Back camera' }];
        await sequence.retryCamera();
        await flush();

        expect(sequence.hasError).toBe(false);
        expect(sequence.isScanning).toBe(true);
    });
});
