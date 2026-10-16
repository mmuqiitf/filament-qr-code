// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

function accepted(code, extra = {}) {
    return { passes: true, code, source: 'camera', message: null, record_key: null, ...extra };
}

function rejected(code, message) {
    return { passes: false, code, source: 'camera', message, record_key: null };
}

function mountChecked({ respond = (key, method, { code }) => Promise.resolve(accepted(code)), ...options } = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"></div>';
    const element = document.querySelector('[data-field-name="sku"]');
    const wire = createWireSpy({ respond });
    const scanner = mountComponent(
        qrScannerComponent({
            statePath: 'data.sku',
            sound: false,
            vibrate: false,
            hardwareScanner: false,
            serverCheck: true,
            componentKey: 'form.sku',
            cooldownMs: 1500,
            ...options,
        }),
        { element, wire },
    );

    return { scanner, wire };
}

function captureWindowEvent(name) {
    const seen = [];
    const listener = (event) => seen.push(event.detail);
    window.addEventListener(name, listener);
    seenListeners.push([name, listener]);

    return seen;
}

const seenListeners = [];

afterEach(() => {
    document.body.innerHTML = '';
    seenListeners.splice(0).forEach(([name, listener]) => window.removeEventListener(name, listener));
});

describe('qrScanner server checks', () => {
    it('calls handleScan once via callSchemaComponentMethod when serverCheck is true and accepts the result', async () => {
        const { scanner, wire } = mountChecked({
            respond: () => Promise.resolve(accepted('SKU-1', { source: 'hardware', record_key: 7 })),
        });
        const acceptedEvents = captureWindowEvent('qr-scan-accepted');
        const scannedEvents = captureWindowEvent('qr-scanned');

        const result = await scanner.handleScanResult('  SKU-1 ', 'hardware');

        expect(wire.methodCalls).toEqual([['form.sku', 'handleScan', { code: 'SKU-1', source: 'hardware' }]]);
        expect(wire.calls).toEqual([]);
        expect(result.passes).toBe(true);
        expect(scanner.value).toBe('SKU-1');
        expect(acceptedEvents).toEqual([{ value: 'SKU-1', field: 'sku', source: 'hardware', recordKey: 7 }]);
        expect(scannedEvents).toEqual([{ value: 'SKU-1', field: 'sku', nextField: null, source: 'hardware', recordKey: 7 }]);
    });

    it('keeps the unconfigured path unchanged: no server call, one $wire.set', () => {
        const { scanner, wire } = mountChecked({ serverCheck: false, cooldownMs: 0 });

        scanner.handleScanResult('PRD-1001');

        expect(wire.methodCalls).toEqual([]);
        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
    });

    it('falls back to the unconfigured path when the component key is missing', () => {
        const { scanner, wire } = mountChecked({ componentKey: null });

        scanner.handleScanResult('PRD-1001');

        expect(wire.methodCalls).toEqual([]);
        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
    });

    it('rejected result clears the value and dispatches qr-scan-rejected once', async () => {
        const { scanner } = mountChecked({ respond: () => Promise.resolve(rejected('SKU-404', 'No record found for "SKU-404".')) });
        const rejectedEvents = captureWindowEvent('qr-scan-rejected');
        const scannedEvents = captureWindowEvent('qr-scanned');
        scanner.value = 'OLD';

        const result = await scanner.handleScanResult('SKU-404');

        expect(result.passes).toBe(false);
        expect(scanner.value).toBe('');
        expect(rejectedEvents).toEqual([
            { value: 'SKU-404', field: 'sku', source: 'camera', message: 'No record found for "SKU-404".' },
        ]);
        expect(scannedEvents).toEqual([]);
    });

    it('keeps the value on reject when clearOnReject is off', async () => {
        const { scanner } = mountChecked({ clearOnReject: false, respond: () => Promise.resolve(rejected('X', 'Nope')) });
        scanner.value = 'OLD';

        await scanner.handleScanResult('X');

        expect(scanner.value).toBe('OLD');
    });

    it.each([
        ['a null result', () => Promise.resolve(null)],
        ['a rejected call', () => Promise.reject(new Error('offline'))],
    ])('turns %s into a rejected scan with the scan_failed message', async (label, respond) => {
        const errors = [];
        const originalError = console.error;
        console.error = (...args) => errors.push(args);

        try {
            const { scanner } = mountChecked({ respond, scanFailedMessage: 'The scan could not be checked. Please try again.' });
            const rejectedEvents = captureWindowEvent('qr-scan-rejected');

            await scanner.handleScanResult('SKU-1');

            expect(rejectedEvents.map(({ message }) => message)).toEqual(['The scan could not be checked. Please try again.']);
        } finally {
            console.error = originalError;
        }
    });

    it('same code within cooldown is dropped and emits qr-scan-duplicate', async () => {
        const { scanner, wire } = mountChecked();
        const duplicates = captureWindowEvent('qr-scan-duplicate');

        await scanner.handleScanResult('SKU-1');
        await scanner.handleScanResult('SKU-1');

        expect(wire.methodCalls).toHaveLength(1);
        expect(duplicates).toEqual([{ value: 'SKU-1', field: 'sku', source: 'camera' }]);
    });

    it('ignores a second scan while a check is in flight', async () => {
        const pending = [];
        const { scanner, wire } = mountChecked({
            respond: (key, method, { code }) => new Promise((resolve) => pending.push(() => resolve(accepted(code)))),
        });

        const first = scanner.handleScanResult('SKU-1');
        const second = await scanner.handleScanResult('SKU-2');

        expect(second).toEqual({ passes: false, ignored: true });
        expect(wire.methodCalls).toHaveLength(1);

        pending[0]();
        await first;

        expect(scanner.isChecking).toBe(false);
    });

    it('pauses the camera during a check and resumes it after a reject', async () => {
        const cameraCalls = [];
        const { scanner } = mountChecked({ respond: () => Promise.resolve(rejected('X', 'Nope')) });
        scanner.html5Qrcode = {
            pause: (shouldPauseVideo) => cameraCalls.push(['pause', shouldPauseVideo]),
            resume: () => cameraCalls.push(['resume']),
        };
        scanner.isScanning = true;
        scanner.isModalOpen = true;

        await scanner.onCameraDecode('X');

        expect(cameraCalls).toEqual([['pause', true], ['resume']]);
        expect(scanner.isModalOpen).toBe(true);
    });

    it('closes the scanner after an accepted camera scan', async () => {
        const { scanner } = mountChecked();
        const closes = [];
        scanner.html5Qrcode = { pause: () => {}, resume: () => {} };
        scanner.closeScannerModal = () => closes.push('close');

        await scanner.onCameraDecode('SKU-1');

        expect(closes).toEqual(['close']);
    });

    it('claims qr-scan-deliver events and checks them as hardware scans', async () => {
        const { scanner, wire } = mountChecked();
        const input = document.querySelector('[data-field-name="sku"] input');
        const event = new CustomEvent('qr-scan-deliver', { bubbles: true, cancelable: true, detail: { value: 'SKU-9' } });

        const notCancelled = input.dispatchEvent(event);
        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(notCancelled).toBe(false);
        expect(wire.methodCalls).toEqual([['form.sku', 'handleScan', { code: 'SKU-9', source: 'hardware' }]]);
        expect(scanner.isChecking).toBe(false);
    });

    it('does not claim qr-scan-deliver without server checks', () => {
        mountChecked({ serverCheck: false });
        const input = document.querySelector('[data-field-name="sku"] input');

        expect(input.dispatchEvent(new CustomEvent('qr-scan-deliver', { bubbles: true, cancelable: true, detail: { value: 'X' } }))).toBe(true);
    });
});
