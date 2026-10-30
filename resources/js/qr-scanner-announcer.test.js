// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

function mountScanner(options = {}, wire = createWireSpy()) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"></div>';
    const element = document.querySelector('[data-field-name="sku"]');

    return mountComponent(
        qrScannerComponent({ statePath: 'data.sku', sound: false, vibrate: false, hardwareScanner: false, fieldLabel: 'SKU', ...options }),
        { element, wire },
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

    it('stays silent when announcements are off', () => {
        const scanner = mountScanner({ announcements: false });

        scanner.acceptLocally('PRD-1', 'camera', 'sku');
        scanner.applyRejected({ code: 'BAD', source: 'hardware', message: 'No.' }, 'sku');

        expect(scanner.announcement).toBe('');
    });

    it('reads custom text from the server for accepted and rejected scans', async () => {
        vi.useFakeTimers();
        const wire = createWireSpy({ respond: (key, method, args) => (args.accepted ? `In: ${args.code}` : `Out: ${args.reason}`) });
        const scanner = mountScanner({ customAnnouncements: true, componentKey: 'form.sku' }, wire);

        scanner.acceptLocally('PRD-1', 'camera', 'sku');
        await vi.runAllTimersAsync();
        expect(scanner.announcement).toBe('In: PRD-1');

        scanner.applyRejected({ code: 'BAD', source: 'hardware', message: 'Unknown product.' }, 'sku');
        await vi.runAllTimersAsync();
        expect(scanner.announcement).toBe('Out: Unknown product.');
        expect(wire.methodCalls.at(-1)).toEqual(['form.sku', 'announceScan', { code: 'BAD', accepted: false, reason: 'Unknown product.', field: null }]);
        vi.useRealTimers();
    });

    it('keeps only the newest custom text when replies arrive out of order', async () => {
        vi.useFakeTimers();
        const replies = [];
        const wire = createWireSpy({ respond: (key, method, args) => new Promise((resolve) => replies.push(() => resolve(`In: ${args.code}`))) });
        const scanner = mountScanner({ customAnnouncements: true, componentKey: 'form.sku' }, wire);

        scanner.acceptLocally('PRD-1', 'camera', 'sku');
        scanner.acceptLocally('PRD-2', 'camera', 'sku');
        await vi.runAllTimersAsync();
        expect(replies).toHaveLength(2);
        replies[1]();
        replies[0]();
        await vi.runAllTimersAsync();

        expect(scanner.announcement).toBe('In: PRD-2');
        vi.useRealTimers();
    });
});
