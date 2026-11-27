// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const decodeResults = new Map();

vi.mock('html5-qrcode', () => {
    class FakeHtml5Qrcode {
        static instances = [];

        constructor(elementId) {
            this.elementId = elementId;
            this.cameraRunning = false;
            this.calls = [];
            FakeHtml5Qrcode.instances.push(this);
        }

        static getCameras() {
            if (FakeHtml5Qrcode.deferGetCameras) {
                return new Promise((resolve) => {
                    FakeHtml5Qrcode.resolveGetCameras = () => resolve([{ id: 'cam-1', label: 'Fake camera' }]);
                });
            }

            return Promise.resolve([{ id: 'cam-1', label: 'Fake camera' }]);
        }

        start() {
            this.calls.push('start');
            if (FakeHtml5Qrcode.deferStart) {
                this.cameraRunning = true;

                return new Promise((resolve) => {
                    FakeHtml5Qrcode.resolveStart = resolve;
                });
            }
            this.cameraRunning = true;

            return Promise.resolve();
        }

        stop() {
            this.calls.push('stop');
            if (FakeHtml5Qrcode.failStop) {
                return Promise.reject(new TypeError("Failed to execute 'removeChild' on 'Node': parameter 1 is not of type 'Node'."));
            }
            this.cameraRunning = false;

            return Promise.resolve();
        }

        // Mirrors html5-qrcode 2.3.8: a synchronous throw while the camera runs.
        scanFile(file) {
            this.calls.push('scanFile');
            if (this.cameraRunning) {
                throw 'Cannot start file scan - ongoing camera scan';
            }

            return decodeResults.has(file.name)
                ? Promise.resolve(decodeResults.get(file.name))
                : Promise.reject('No MultiFormat Readers were able to detect the code.');
        }
    }

    return { Html5Qrcode: FakeHtml5Qrcode, Html5QrcodeSupportedFormats: {} };
});

const { default: qrScannerComponent } = await import('./qr-scanner.js');
const { createWireSpy, mountComponent } = await import('./alpine-test-harness.js');
const { Html5Qrcode } = await import('html5-qrcode');

async function openScannerWithCamera(options = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"><input type="file"></div>';
    const element = document.querySelector('[data-field-name="sku"]');
    const wire = createWireSpy();
    const scanner = mountComponent(
        qrScannerComponent({ statePath: 'data.sku', sound: false, vibrate: false, hardwareScanner: false, ...options }),
        { element, wire },
    );

    scanner.isModalOpen = true;
    await scanner.loadCamerasAndStart();
    expect(scanner.isScanning).toBe(true);

    return { scanner, wire, fileInput: element.querySelector('input[type="file"]') };
}

function pick(fileInput, name) {
    const file = new File(['png'], name, { type: 'image/png' });
    Object.defineProperty(fileInput, 'files', { value: [file], configurable: true });
    let value = `C:\\fakepath\\${name}`;
    Object.defineProperty(fileInput, 'value', { get: () => value, set: (next) => { value = next; }, configurable: true });

    return { target: fileInput };
}

beforeEach(() => {
    decodeResults.clear();
    Html5Qrcode.instances.length = 0;
    Html5Qrcode.deferStart = false;
    Html5Qrcode.resolveStart = null;
    Html5Qrcode.deferGetCameras = false;
    Html5Qrcode.resolveGetCameras = null;
    Html5Qrcode.failStop = false;
    vi.spyOn(console, 'debug').mockImplementation(() => {});
});

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = '';
});

describe('qrScanner scan from image file', () => {
    it('decodes an uploaded image while the camera is running', async () => {
        decodeResults.set('prd-1001.png', 'PRD-1001');
        const { scanner, wire, fileInput } = await openScannerWithCamera();

        await scanner.scanFile(pick(fileInput, 'prd-1001.png'));

        expect(scanner.hasError).toBe(false);
        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
        expect(Html5Qrcode.instances[0].calls).toEqual(['start', 'stop', 'scanFile']);
    });

    it('shows the translated message and restarts the camera when the image has no code', async () => {
        const { scanner, fileInput } = await openScannerWithCamera({ fileScanFailedMessage: 'Kode QR tidak terbaca.' });

        await scanner.scanFile(pick(fileInput, 'blank.png'));

        expect(scanner.hasError).toBe(true);
        expect(scanner.errorMessage).toBe('Kode QR tidak terbaca.');
        expect(scanner.isScanning).toBe(true);
        expect(console.debug).toHaveBeenCalled();
    });

    it('resets the file input so the same file can be picked again', async () => {
        const { scanner, fileInput } = await openScannerWithCamera();

        await scanner.scanFile(pick(fileInput, 'blank.png'));

        expect(fileInput.value).toBe('');
    });

    it('waits for an in-flight camera start to settle before decoding an uploaded file', async () => {
        decodeResults.set('prd-2002.png', 'PRD-2002');
        Html5Qrcode.deferStart = true;

        document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"><input type="file"></div>';
        const element = document.querySelector('[data-field-name="sku"]');
        const wire = createWireSpy();
        const scanner = mountComponent(
            qrScannerComponent({ statePath: 'data.sku', sound: false, vibrate: false, hardwareScanner: false }),
            { element, wire },
        );

        scanner.isModalOpen = true;
        const startPromise = scanner.loadCamerasAndStart();
        await vi.waitFor(() => expect(Html5Qrcode.resolveStart).toBeTypeOf('function'));

        const fileInput = element.querySelector('input[type="file"]');
        const fileScanPromise = scanner.scanFile(pick(fileInput, 'prd-2002.png'));

        // Keep start() in flight past scanFile()'s initial microtasks, just like a slow getUserMedia().
        await new Promise((resolve) => setTimeout(resolve, 20));
        Html5Qrcode.resolveStart?.();
        await startPromise;
        await fileScanPromise;

        expect(scanner.hasError).toBe(false);
        expect(wire.calls).toEqual([['data.sku', 'PRD-2002']]);
    });

    it('cancels a pending device enumeration when a file is picked before startScan runs', async () => {
        decodeResults.set('prd-3003.png', 'PRD-3003');
        Html5Qrcode.deferGetCameras = true;

        document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"><input type="file"></div>';
        const element = document.querySelector('[data-field-name="sku"]');
        const wire = createWireSpy();
        const scanner = mountComponent(
            qrScannerComponent({ statePath: 'data.sku', sound: false, vibrate: false, hardwareScanner: false }),
            { element, wire },
        );

        scanner.isModalOpen = true;
        const startPromise = scanner.loadCamerasAndStart();
        await vi.waitFor(() => expect(Html5Qrcode.resolveGetCameras).toBeTypeOf('function'));

        const fileInput = element.querySelector('input[type="file"]');
        const fileScanPromise = scanner.scanFile(pick(fileInput, 'prd-3003.png'));

        await new Promise((resolve) => setTimeout(resolve, 20));
        Html5Qrcode.resolveGetCameras?.();
        await startPromise;
        await fileScanPromise;

        expect(scanner.hasError).toBe(false);
        expect(scanner.isScanning).toBe(false);
        expect(wire.calls).toEqual([['data.sku', 'PRD-3003']]);
        expect(Html5Qrcode.instances[0].calls).toEqual(['scanFile']);
    });

    it('discards a wedged decoder instance if stop() throws before scanning an uploaded file', async () => {
        decodeResults.set('prd-4004.png', 'PRD-4004');
        const { scanner, wire, fileInput } = await openScannerWithCamera();
        Html5Qrcode.failStop = true;

        await scanner.scanFile(pick(fileInput, 'prd-4004.png'));

        expect(scanner.hasError).toBe(false);
        expect(wire.calls).toEqual([['data.sku', 'PRD-4004']]);
        expect(Html5Qrcode.instances).toHaveLength(2);
    });
});
