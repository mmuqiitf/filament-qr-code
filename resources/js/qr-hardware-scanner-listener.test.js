// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import { qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import { mountComponent } from './alpine-test-harness.js';

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
