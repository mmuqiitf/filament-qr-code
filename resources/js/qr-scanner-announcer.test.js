// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

function mountScanner(options = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"></div>';
    const element = document.querySelector('[data-field-name="sku"]');

    return mountComponent(
        qrScannerComponent({ statePath: 'data.sku', sound: false, vibrate: false, hardwareScanner: false, fieldLabel: 'SKU', ...options }),
        { element, wire: createWireSpy() },
    );
}

afterEach(() => {
    document.body.innerHTML = '';
});

describe('qrScanner announcements', () => {
    it('announces a locally accepted scan with the field label', () => {
        const scanner = mountScanner();

        scanner.acceptLocally('PRD-1', 'camera', 'sku');

        expect(scanner.announcement).toBe('Added PRD-1 to SKU');
    });

    it('announces a server-accepted scan', () => {
        const scanner = mountScanner({ fieldAddedAnnouncement: 'Ditambahkan :code ke :field' });

        scanner.applyAccepted({ code: 'PRD-2', source: 'hardware' }, 'sku');

        expect(scanner.announcement).toBe('Ditambahkan PRD-2 ke SKU');
    });

    it('announces a rejection with its message', () => {
        const scanner = mountScanner();

        scanner.applyRejected({ code: 'BAD', source: 'hardware', message: 'Unknown product.' }, 'sku');

        expect(scanner.announcement).toBe('Rejected: Unknown product.');
    });

    it('falls back to the generic rejection when there is no message', () => {
        const scanner = mountScanner();

        scanner.applyRejected({ code: 'BAD', source: 'hardware', message: null }, 'sku');

        expect(scanner.announcement).toBe('Rejected: This code was rejected.');
    });
});
