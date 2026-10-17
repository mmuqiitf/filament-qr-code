// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { inputRouter } from './input-router.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

const accepted = (code) => ({ passes: true, code, source: 'hardware', message: null, record_key: null });
const rejected = (code, message) => ({ passes: false, code, source: 'hardware', message, record_key: null });

function deferred() {
    let resolve;
    const promise = new Promise((done) => {
        resolve = done;
    });

    return { promise, resolve };
}

function mountContinuous({ respond = (key, method, { code }) => Promise.resolve(accepted(code)), ...options } = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"><div data-qr-scanner-panel></div></div>';
    const wire = createWireSpy({ respond });
    const scanner = mountComponent(qrScannerComponent({
        statePath: 'data.sku',
        sound: false,
        vibrate: false,
        hardwareScanner: false,
        serverCheck: true,
        componentKey: 'form.sku',
        cooldownMs: 0,
        mode: 'continuous',
        successFeedback: { sound: false, vibrate: [], flash: false },
        errorFeedback: { sound: false, vibrate: [], flash: false },
        ...options,
    }), { element: document.querySelector('[data-field-name="sku"]'), wire });

    return { scanner, wire };
}

const cleanups = [];

function captureEvents(name) {
    const seen = [];
    const record = (event) => seen.push(event.detail);
    window.addEventListener(name, record);
    cleanups.push(() => window.removeEventListener(name, record));

    return seen;
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

afterEach(() => {
    cleanups.splice(0).forEach((cleanup) => cleanup());
    inputRouter().destroy();
    document.body.innerHTML = '';
});

describe('QrScanner continuous mode', () => {
    it('continuous mode keeps state empty and calls onScanned per accepted code', async () => {
        const { scanner, wire } = mountContinuous();
        const acceptedEvents = captureEvents('qr-scan-accepted');

        for (const code of ['A-1', 'A-2', 'A-3']) {
            await scanner.handleScanResult(code, 'hardware');

            expect(scanner.value).toBe('');
        }

        expect(wire.methodCalls.map(([, method, { code }]) => [method, code])).toEqual([
            ['handleScan', 'A-1'],
            ['handleScan', 'A-2'],
            ['handleScan', 'A-3'],
        ]);
        expect(acceptedEvents.map(({ value, mode }) => [value, mode])).toEqual([
            ['A-1', 'continuous'],
            ['A-2', 'continuous'],
            ['A-3', 'continuous'],
        ]);
    });

    it('last_code display keeps the code', async () => {
        const { scanner } = mountContinuous({ continuousDisplay: 'last_code' });

        await scanner.handleScanResult('A-1', 'hardware');

        expect(scanner.value).toBe('A-1');
    });

    it('reject shows the message and keeps scanning', async () => {
        const { scanner, wire } = mountContinuous({
            respond: (key, method, { code }) => Promise.resolve(code === 'BAD' ? rejected(code, 'Unknown code.') : accepted(code)),
        });

        await scanner.handleScanResult('BAD', 'hardware');

        expect(scanner.rejectionMessage).toBe('Unknown code.');
        expect(scanner.isPaused).toBe(false);

        await scanner.handleScanResult('A-1', 'hardware');

        expect(scanner.rejectionMessage).toBe('');
        expect(wire.methodCalls).toHaveLength(2);
    });

    it('pauseOnReject refuses scans until resumed and emits qr-scan-paused and qr-scan-resumed', async () => {
        const { scanner, wire } = mountContinuous({
            pauseOnReject: true,
            respond: (key, method, { code }) => Promise.resolve(rejected(code, 'Unknown code.')),
        });
        const paused = captureEvents('qr-scan-paused');
        const resumed = captureEvents('qr-scan-resumed');

        await scanner.handleScanResult('BAD', 'hardware');
        const ignored = await scanner.handleScanResult('NEXT', 'hardware');

        expect(scanner.isPaused).toBe(true);
        expect(ignored.ignored).toBe(true);
        expect(wire.methodCalls).toHaveLength(1);
        expect(paused).toEqual([{ field: 'sku' }]);

        scanner.resumeScanning();

        expect(scanner.isPaused).toBe(false);
        expect(scanner.rejectionMessage).toBe('');
        expect(resumed).toEqual([{ field: 'sku' }]);
    });

    it('camera modal closes after each accepted scan by default', async () => {
        const { scanner } = mountContinuous();
        const calls = [];
        scanner.isModalOpen = true;
        scanner.isScanning = true;
        scanner.html5Qrcode = {
            pause: () => calls.push('pause'),
            resume: () => calls.push('resume'),
            stop: () => {
                calls.push('stop');

                return Promise.resolve();
            },
        };

        await scanner.onCameraDecode('A-1');
        await flush();

        expect(calls).toEqual(['pause', 'stop']);
        expect(scanner.isModalOpen).toBe(false);
    });

    it('keepCameraOpen resumes the camera instead', async () => {
        const { scanner } = mountContinuous({ keepCameraOpen: true });
        const calls = [];
        scanner.isModalOpen = true;
        scanner.html5Qrcode = { pause: () => calls.push('pause'), resume: () => calls.push('resume') };

        await scanner.onCameraDecode('A-1');

        expect(calls).toEqual(['pause', 'resume']);
        expect(scanner.isModalOpen).toBe(true);
    });

    it('keeps the camera paused after a reject with pauseOnReject until resumed', async () => {
        const { scanner } = mountContinuous({
            pauseOnReject: true,
            respond: (key, method, { code }) => Promise.resolve(rejected(code, 'Unknown code.')),
        });
        const calls = [];
        scanner.isModalOpen = true;
        scanner.html5Qrcode = { pause: () => calls.push('pause'), resume: () => calls.push('resume') };

        await scanner.onCameraDecode('BAD');

        expect(calls).toEqual(['pause']);

        scanner.resumeScanning();

        expect(calls).toEqual(['pause', 'resume']);
    });

    it('a burst during a check is queued and replayed', async () => {
        const pending = deferred();
        const { scanner, wire } = mountContinuous({
            respond: (key, method, { code }) => (code === 'A-1' ? pending.promise : Promise.resolve(accepted(code))),
        });

        const first = scanner.handleScanResult('A-1', 'hardware');

        expect(scanner.holdScan('A-2', 'hardware')).toBe(true);

        pending.resolve(accepted('A-1'));
        await first;
        await flush();

        expect(wire.methodCalls.map(([, , { code }]) => code)).toEqual(['A-1', 'A-2']);
    });

    it('a second burst during a check is dropped with qr-scan-dropped', async () => {
        const pending = deferred();
        const { scanner } = mountContinuous({ respond: () => pending.promise });
        const dropped = captureEvents('qr-scan-dropped');

        scanner.handleScanResult('A-1', 'hardware');
        scanner.holdScan('A-2', 'hardware');

        expect(scanner.holdScan('A-3', 'hardware')).toBe(true);
        expect(dropped).toEqual([{ value: 'A-3', field: 'sku', source: 'hardware' }]);

        pending.resolve(accepted('A-1'));
    });

    it('a queued code is dropped when the field pauses', async () => {
        const pending = deferred();
        const { scanner, wire } = mountContinuous({ pauseOnReject: true, respond: () => pending.promise });
        const dropped = captureEvents('qr-scan-dropped');

        const first = scanner.handleScanResult('BAD', 'hardware');
        scanner.holdScan('A-2', 'hardware');
        pending.resolve(rejected('BAD', 'Unknown code.'));
        await first;
        await flush();

        expect(wire.methodCalls).toHaveLength(1);
        expect(dropped).toEqual([{ value: 'A-2', field: 'sku', source: 'hardware' }]);
    });

    it('single mode never holds a burst', () => {
        const { scanner } = mountContinuous({ mode: 'single' });

        expect(scanner.holdScan('A-1', 'hardware')).toBe(false);
    });
});
