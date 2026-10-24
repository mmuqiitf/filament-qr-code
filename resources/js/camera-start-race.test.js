// @vitest-environment happy-dom
import { beforeEach, describe, expect, it, vi } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import qrCollectorComponent from './qr-collector.js';
import qrScanSequenceComponent from './qr-sequence.js';
import { mountComponent } from './alpine-test-harness.js';

// A real camera takes 0.5-2 s to start. Closing the modal (or Livewire
// removing the component) during that window used to leave the stream
// running: the stop saw isScanning === false and did nothing, then start()
// resolved and flipped isScanning on for a component nobody can see.
const camera = vi.hoisted(() => ({ release: null, stop: null }));

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: async () => ({
        Html5Qrcode: { getCameras: async () => [{ id: 'cam-1', label: 'Back camera' }] },
        Html5QrcodeSupportedFormats: {},
    }),
    ensureScannerInstance: (component) => {
        component.html5Qrcode = {
            start: () => new Promise((resolve) => { camera.release = resolve; }),
            stop: camera.stop,
            clear: () => {},
        };
    },
    refreshCameraControls: () => {},
}));

const flush = async () => {
    for (let i = 0; i < 10; i++) await new Promise((resolve) => setTimeout(resolve, 0));
};

const element = () => document.body.appendChild(document.createElement('div'));

describe('camera stopped while start() is pending', () => {
    beforeEach(() => {
        camera.release = null;
        camera.stop = vi.fn(async () => {});
    });

    it('qrScanner: closing the modal mid-start stops the stream once it opens', async () => {
        const scanner = mountComponent(qrScannerComponent({}), { element: element() });
        scanner.openScannerModal();
        await flush();
        expect(camera.release).toBeTypeOf('function');

        scanner.closeScannerModal();
        await flush();
        camera.release();
        await flush();

        expect(camera.stop).toHaveBeenCalled();
        expect(scanner.isScanning).toBe(false);
    });

    it('qrScanner: a modal closed before the devices load never starts the camera', async () => {
        const scanner = mountComponent(qrScannerComponent({}), { element: element() });
        scanner.openScannerModal();
        scanner.closeScannerModal();
        await flush();

        expect(camera.release).toBeNull();
        expect(scanner.isScanning).toBe(false);
    });

    it('qrCollector: destroy mid-start stops the stream once it opens', async () => {
        const collector = mountComponent(
            qrCollectorComponent({ sound: false, vibrate: false, hardwareScanner: false, autoStart: true }),
            { element: element() },
        );
        await flush();
        expect(camera.release).toBeTypeOf('function');

        collector.destroy();
        await flush();
        camera.release();
        await flush();

        expect(camera.stop).toHaveBeenCalled();
        expect(collector.isScanning).toBe(false);
    });

    it('qrScanSequence: destroy mid-start stops the stream once it opens', async () => {
        const sequence = mountComponent(qrScanSequenceComponent({ fields: [{ key: 'a', label: 'A' }] }), { element: element() });
        await flush();
        sequence.startScanner();
        await flush();
        expect(camera.release).toBeTypeOf('function');

        sequence.destroy();
        await flush();
        camera.release();
        await flush();

        expect(camera.stop).toHaveBeenCalled();
        expect(sequence.isScanning).toBe(false);
    });
});
