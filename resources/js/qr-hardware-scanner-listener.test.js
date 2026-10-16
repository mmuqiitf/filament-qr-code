// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import { qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

function mountListener(options = {}) {
    const element = document.createElement('div');
    document.body.appendChild(element);

    return mountComponent(
        qrHardwareScannerListenerComponent({ sound: false, vibrate: false, autoFocusNext: false, ...options }),
        { element },
    );
}

function captureUnrouted() {
    const seen = [];
    window.addEventListener('qr-scan-unrouted', (event) => seen.push(event.detail));

    return seen;
}

afterEach(() => {
    document.body.innerHTML = '';
});

describe('qrHardwareScannerListener routing (P0.3)', () => {
    it('keeps the v0.2 fallback into the first empty form input by default', () => {
        document.body.innerHTML = '<form><input type="search" name="search"></form>';
        const listener = mountListener({ fields: ['sku'] });

        listener.handleGlobalScan('PRD-1001');

        expect(document.querySelector('[name="search"]').value).toBe('PRD-1001');
    });

    it('never writes into an unrelated input when the fallback is disabled', () => {
        document.body.innerHTML = '<form><input type="search" name="search"></form>';
        const unrouted = captureUnrouted();
        const listener = mountListener({ fields: ['sku'], fallbackToAnyInput: false });

        listener.handleGlobalScan('PRD-1001');

        expect(document.querySelector('[name="search"]').value).toBe('');
        expect(unrouted).toEqual([{ value: 'PRD-1001' }]);
    });

    it('still routes into a registered empty field when the fallback is disabled', () => {
        document.body.innerHTML = '<form><input type="search" name="search"><input type="text" name="sku"></form>';
        const listener = mountListener({ fields: ['sku'], fallbackToAnyInput: false });

        listener.handleGlobalScan('PRD-1001');

        expect(document.querySelector('[name="sku"]').value).toBe('PRD-1001');
        expect(document.querySelector('[name="search"]').value).toBe('');
    });

    it('dispatches qr-scan-unrouted when no target exists at all', () => {
        const unrouted = captureUnrouted();
        const listener = mountListener();

        listener.handleGlobalScan('PRD-1001');

        expect(unrouted).toEqual([{ value: 'PRD-1001' }]);
    });
});

describe('qrHardwareScannerListener server-checked fields (P1a stopgap)', () => {
    it('global listener hands a burst to a server-checked scanner field instead of writing $wire.set', () => {
        document.body.innerHTML = '<form><div data-field-name="sku"><input type="text" name="sku" wire:model="data.sku"></div></form>';
        const input = document.querySelector('[name="sku"]');
        const delivered = [];
        input.closest('[data-field-name]').addEventListener('qr-scan-deliver', (event) => {
            event.preventDefault();
            delivered.push(event.detail.value);
        });
        const wire = createWireSpy();
        const element = document.createElement('div');
        document.body.appendChild(element);
        const listener = mountComponent(
            qrHardwareScannerListenerComponent({ fields: ['sku'], sound: false, vibrate: false, autoFocusNext: false }),
            { element, wire },
        );

        listener.handleGlobalScan('SKU-1');

        expect(delivered).toEqual(['SKU-1']);
        expect(input.value).toBe('');
        expect(wire.calls).toEqual([]);
    });

    it('keeps writing directly when no field claims the burst', () => {
        document.body.innerHTML = '<form><input type="text" name="sku" wire:model="data.sku"></form>';
        const wire = createWireSpy();
        const element = document.createElement('div');
        document.body.appendChild(element);
        const listener = mountComponent(
            qrHardwareScannerListenerComponent({ fields: ['sku'], sound: false, vibrate: false, autoFocusNext: false }),
            { element, wire },
        );

        listener.handleGlobalScan('SKU-1');

        expect(document.querySelector('[name="sku"]').value).toBe('SKU-1');
        expect(wire.calls).toEqual([['data.sku', 'SKU-1']]);
    });
});
