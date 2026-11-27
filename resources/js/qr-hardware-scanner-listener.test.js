// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import { qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import { inputRouter } from './input-router.js';
import { clearDocument, createWireSpy, mountComponent } from './alpine-test-harness.js';

function mountListener(options = {}, parent = document.body) {
    const element = document.createElement('div');
    parent.appendChild(element);

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
    inputRouter().destroy();
    clearDocument();
});

describe('qrHardwareScannerListener routing (P0.3)', () => {
    it('fills the first empty input in the listener form', () => {
        document.body.innerHTML = '<form id="station"><input type="text" name="note"><input type="text" name="bin" value="A1"></form>';
        const listener = mountListener({ fields: ['sku'] }, document.getElementById('station'));

        listener.handleGlobalScan('PRD-1001');

        expect(document.querySelector('[name="note"]').value).toBe('PRD-1001');
        expect(document.querySelector('[name="bin"]').value).toBe('A1');
    });

    it('does not fill an empty input in another form', () => {
        document.body.innerHTML = '<form id="filters"><input type="search" name="search"></form><form id="station"><input type="text" name="note"></form>';
        const unrouted = captureUnrouted();
        const listener = mountListener({ fields: ['sku'] }, document.getElementById('station'));

        listener.handleGlobalScan('PRD-1001');

        expect(document.querySelector('[name="search"]').value).toBe('');
        expect(document.querySelector('[name="note"]').value).toBe('PRD-1001');
        expect(unrouted).toEqual([]);
    });

    it('leaves another form alone when the listener form has no empty input', () => {
        document.body.innerHTML = '<form id="filters"><input type="search" name="search"></form><form id="station"><input type="text" name="note" value="kept"></form>';
        const unrouted = captureUnrouted();
        const listener = mountListener({ fields: ['sku'] }, document.getElementById('station'));

        listener.handleGlobalScan('PRD-1001');

        expect(document.querySelector('[name="search"]').value).toBe('');
        expect(document.querySelector('[name="note"]').value).toBe('kept');
        expect(unrouted).toEqual([{ value: 'PRD-1001' }]);
    });

    it('never writes into an unrelated input when the fallback is disabled', () => {
        document.body.innerHTML = '<form id="station"><input type="search" name="search"></form>';
        const unrouted = captureUnrouted();
        const listener = mountListener({ fields: ['sku'], fallbackToAnyInput: false }, document.getElementById('station'));

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
