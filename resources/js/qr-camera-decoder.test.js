import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('html5-qrcode', () => ({
    Html5Qrcode: class FakeHtml5Qrcode {},
    Html5QrcodeSupportedFormats: { QR_CODE: 0 },
    marker: 'html5-qrcode',
}));

import {
    applyTorch,
    applyZoom,
    cameraCapabilitiesFor,
    createNativeDecoderModule,
    nativeFormatFor,
    resolveDecoderModule,
    setDecoder,
} from './qr-camera-core.js';

function fakeDetector(supported = ['qr_code', 'code_128'], detections = [{ rawValue: 'NATIVE-1' }]) {
    const detect = vi.fn(async () => detections);

    class FakeBarcodeDetector {
        constructor(options) {
            this.options = options;
        }

        detect(source) {
            return detect(source);
        }

        static async getSupportedFormats() {
            return supported;
        }
    }

    return { FakeBarcodeDetector, detect };
}

afterEach(() => {
    setDecoder(null);
    vi.unstubAllGlobals();
});

describe('default decoder loader', () => {
    it('uses BarcodeDetector when available, else html5-qrcode', async () => {
        const withoutNative = await resolveDecoderModule(['QR_CODE']);
        expect(withoutNative.marker).toBe('html5-qrcode');

        setDecoder(null);
        vi.stubGlobal('BarcodeDetector', fakeDetector().FakeBarcodeDetector);

        const withNative = await resolveDecoderModule(['QR_CODE']);
        expect(withNative.native).toBe(true);
        expect(typeof withNative.Html5Qrcode).toBe('function');
    });

    it('falls back to html5-qrcode when the native detector lacks a requested format', async () => {
        vi.stubGlobal('BarcodeDetector', fakeDetector(['qr_code']).FakeBarcodeDetector);

        const decoder = await resolveDecoderModule(['QR_CODE', 'MAXICODE']);

        expect(decoder.marker).toBe('html5-qrcode');
    });

    it('caches per format set', async () => {
        vi.stubGlobal('BarcodeDetector', fakeDetector(['qr_code']).FakeBarcodeDetector);

        const qr = await resolveDecoderModule(['QR_CODE']);
        const again = await resolveDecoderModule(['QR_CODE']);
        const other = await resolveDecoderModule(['AZTEC']);

        expect(again).toBe(qr);
        expect(other.marker).toBe('html5-qrcode');
    });
});

describe('setDecoder', () => {
    it('replaces the loader and receives the requested formats', async () => {
        const custom = { Html5Qrcode: class {}, Html5QrcodeSupportedFormats: {} };
        const loader = vi.fn(async () => custom);

        setDecoder(loader);

        expect(await resolveDecoderModule(['CODE_128'])).toBe(custom);
        expect(loader).toHaveBeenCalledWith({ formats: ['CODE_128'] });
    });

    it('restores the default loader with null', async () => {
        setDecoder(() => ({ custom: true }));
        setDecoder(null);

        expect((await resolveDecoderModule([])).marker).toBe('html5-qrcode');
    });

    it('rejects a non-function loader', () => {
        expect(() => setDecoder('nope')).toThrow(TypeError);
    });
});

describe('native decoder module', () => {
    it('maps PHP format names to BarcodeDetector names', () => {
        expect(nativeFormatFor('QR_CODE')).toBe('qr_code');
        expect(nativeFormatFor('PDF_417')).toBe('pdf417');
        expect(nativeFormatFor('ITF')).toBe('itf');
    });

    it('lists cameras through enumerateDevices', async () => {
        vi.stubGlobal('navigator', {
            mediaDevices: {
                getUserMedia: vi.fn(async () => ({ getTracks: () => [{ stop: vi.fn() }] })),
                enumerateDevices: vi.fn(async () => [
                    { kind: 'videoinput', deviceId: 'cam-1', label: 'Back camera' },
                    { kind: 'audioinput', deviceId: 'mic', label: 'Mic' },
                ]),
            },
        });
        const { FakeBarcodeDetector } = fakeDetector();
        const module = createNativeDecoderModule(FakeBarcodeDetector);

        expect(await module.Html5Qrcode.getCameras()).toEqual([{ id: 'cam-1', label: 'Back camera' }]);
    });

    it('decodes a file with scanFile', async () => {
        vi.stubGlobal('createImageBitmap', vi.fn(async () => ({ close: vi.fn() })));
        const { FakeBarcodeDetector } = fakeDetector();
        const module = createNativeDecoderModule(FakeBarcodeDetector);
        const instance = new module.Html5Qrcode('reader', { formatsToSupport: ['QR_CODE'] });

        expect(await instance.scanFile(new Blob(['x']), false)).toBe('NATIVE-1');
    });

    it('rejects scanFile when nothing is detected', async () => {
        vi.stubGlobal('createImageBitmap', vi.fn(async () => ({ close: vi.fn() })));
        const { FakeBarcodeDetector } = fakeDetector(['qr_code'], []);
        const module = createNativeDecoderModule(FakeBarcodeDetector);
        const instance = new module.Html5Qrcode('reader', {});

        await expect(instance.scanFile(new Blob(['x']), false)).rejects.toThrow();
    });
});

describe('camera capabilities', () => {
    it('reports torch and zoom from the running track', () => {
        const instance = {
            getRunningTrackCapabilities: () => ({ torch: true, zoom: { min: 1, max: 5, step: 0.5 } }),
        };

        expect(cameraCapabilitiesFor(instance)).toEqual({ torch: true, zoom: { min: 1, max: 5, step: 0.5 } });
    });

    it('reports nothing when capabilities are missing or throw', () => {
        expect(cameraCapabilitiesFor(null)).toEqual({ torch: false, zoom: null });
        expect(cameraCapabilitiesFor({})).toEqual({ torch: false, zoom: null });
        expect(cameraCapabilitiesFor({ getRunningTrackCapabilities: () => { throw new Error('idle'); } }))
            .toEqual({ torch: false, zoom: null });
        expect(cameraCapabilitiesFor({ getRunningTrackCapabilities: () => ({ zoom: { min: 1, max: 1 } }) }))
            .toEqual({ torch: false, zoom: null });
    });

    it('applies torch and zoom as advanced constraints', async () => {
        const applyVideoConstraints = vi.fn(async () => undefined);
        const instance = { applyVideoConstraints };

        expect(await applyTorch(instance, true)).toBe(true);
        expect(await applyZoom(instance, 2)).toBe(true);

        expect(applyVideoConstraints).toHaveBeenNthCalledWith(1, { advanced: [{ torch: true }] });
        expect(applyVideoConstraints).toHaveBeenNthCalledWith(2, { advanced: [{ zoom: 2 }] });
    });

    it('returns false when constraints are rejected', async () => {
        const instance = { applyVideoConstraints: vi.fn(async () => { throw new Error('nope'); }) };

        expect(await applyTorch(instance, true)).toBe(false);
        expect(await applyZoom({}, 2)).toBe(false);
    });
});

describe('refreshCameraControls', () => {
    it('hides controls when the field disables them', async () => {
        const { refreshCameraControls } = await import('./qr-camera-core.js');
        const component = {
            options: { cameraControls: false },
            html5Qrcode: { getRunningTrackCapabilities: () => ({ torch: true }) },
        };

        refreshCameraControls(component);
        expect(component.cameraCaps).toEqual({ torch: false, zoom: null });

        component.options.cameraControls = true;
        refreshCameraControls(component);
        expect(component.cameraCaps.torch).toBe(true);
    });
});
