// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import qrCollectorComponent from './qr-collector.js';
import { mountComponent } from './alpine-test-harness.js';

const camera = vi.hoisted(() => ({ cameras: [], start: null }));

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: async () => ({
        Html5Qrcode: { getCameras: async () => camera.cameras },
        Html5QrcodeSupportedFormats: {},
    }),
    ensureScannerInstance: (component) => {
        component.html5Qrcode = { start: camera.start, stop: async () => {}, clear: () => {}, isScanning: false };
    },
    refreshCameraControls: () => {},
}));

const flush = async () => {
    for (let i = 0; i < 5; i++) await new Promise((resolve) => setTimeout(resolve, 0));
};

const mount = (options = {}) => mountComponent(
    qrCollectorComponent({ sound: false, vibrate: false, hardwareScanner: false, ...options }),
    { element: document.body.appendChild(document.createElement('div')) },
);

describe('qrCollector camera start (#35)', () => {
    beforeEach(() => {
        camera.cameras = [{ id: 'cam-1', label: 'Back camera' }];
        camera.start = vi.fn(async () => {});
    });

    it('starts the camera once devices load when autoStart is on', async () => {
        const collector = mount({ autoStart: true });
        await flush();

        expect(camera.start).toHaveBeenCalledTimes(1);
        expect(collector.isScanning).toBe(true);
    });

    it('waits for an explicit start when autoStart is off', async () => {
        const collector = mount();
        await flush();

        expect(camera.start).not.toHaveBeenCalled();
        expect(collector.isScanning).toBe(false);
    });

    it('loads cameras on demand when Start is pressed before devices resolved', async () => {
        const collector = mount();
        collector.selectedDeviceId = null;
        await collector.startCollector();
        await flush();

        expect(camera.start).toHaveBeenCalled();
        expect(collector.isScanning).toBe(true);
    });

    it('shows an error instead of doing nothing when no camera exists', async () => {
        camera.cameras = [];
        const collector = mount();
        await flush();
        await collector.startCollector();

        expect(camera.start).not.toHaveBeenCalled();
        expect(collector.hasError).toBe(true);
        expect(collector.errorMessage).not.toBe('');
    });

    it('retryCamera clears the error and starts', async () => {
        camera.cameras = [];
        const collector = mount({ autoStart: true });
        await flush();
        expect(collector.hasError).toBe(true);

        camera.cameras = [{ id: 'cam-1', label: 'Back camera' }];
        await collector.retryCamera();
        await flush();

        expect(collector.hasError).toBe(false);
        expect(collector.isScanning).toBe(true);
    });
});
