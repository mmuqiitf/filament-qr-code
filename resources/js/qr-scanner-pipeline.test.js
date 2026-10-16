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
});
