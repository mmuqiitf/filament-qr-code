// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { inputRouter } from './input-router.js';
import { qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import qrCollectorComponent from './qr-collector.js';
import qrScanSequenceComponent from './qr-sequence.js';
import { mountComponent } from './alpine-test-harness.js';

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: () => Promise.reject(new Error('no decoder in tests')),
}));

function mountInto(id, component) {
    return mountComponent(component, { element: document.getElementById(id) });
}

function typeBurst(code) {
    for (const key of [...code, 'Enter']) {
        const target = document.activeElement ?? document.body;
        target.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
        vi.advanceTimersByTime(10);
    }
}

beforeEach(() => {
    vi.useFakeTimers({ now: 1_000_000 });
});

afterEach(() => {
    inputRouter().destroy();
    vi.useRealTimers();
    document.body.innerHTML = '';
});

describe('router migration', () => {
    it('listener and collector on one page deliver a burst exactly once', () => {
        document.body.innerHTML = '<form><input type="text" name="sku"></form><div id="listener"></div><div id="collector"></div>';
        mountInto('listener', qrHardwareScannerListenerComponent({
            fields: ['sku'],
            sound: false,
            vibrate: false,
            autoFocusNext: false,
        }));
        const collector = mountInto('collector', qrCollectorComponent({ sound: false, vibrate: false, delayBetweenScansMs: 0 }));

        typeBurst('PRD-1001');

        expect(document.querySelector('[name="sku"]').value).toBe('PRD-1001');
        expect(collector.items).toEqual([]);
    });

    it('sequence receives a burst through the router', () => {
        document.body.innerHTML = '<div id="sequence"></div>';
        const sequence = mountInto('sequence', qrScanSequenceComponent({
            fields: [{ key: 'po', label: 'PO' }, { key: 'sku', label: 'SKU' }],
            sound: false,
            vibrate: false,
        }));

        typeBurst('PO-77');

        expect(sequence.results).toEqual({ po: 'PO-77' });
        expect(inputRouter().hasTargetOfKind('sequence')).toBe(true);
    });

    it('collector receives a burst when it is the only target', () => {
        document.body.innerHTML = '<div id="collector"></div>';
        const collector = mountInto('collector', qrCollectorComponent({ sound: false, vibrate: false, delayBetweenScansMs: 0 }));

        typeBurst('PRD-1001');

        expect(collector.items.map(({ code }) => code)).toEqual(['PRD-1001']);
    });

    it('listener no longer adds its own window keydown listener', () => {
        document.body.innerHTML = '<div id="listener"></div><div id="collector"></div>';
        const add = vi.spyOn(window, 'addEventListener');

        mountInto('listener', qrHardwareScannerListenerComponent({ sound: false, vibrate: false }));
        mountInto('collector', qrCollectorComponent({ sound: false, vibrate: false }));

        expect(add.mock.calls.filter(([type]) => type === 'keydown')).toHaveLength(1);
        add.mockRestore();
    });
});
