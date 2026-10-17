// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { inputRouter } from './input-router.js';
import { qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import qrCollectorComponent from './qr-collector.js';
import qrScanSequenceComponent from './qr-sequence.js';
import qrScannerComponent from './qr-scanner.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

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

function mountScanner(id, options = {}, wire = createWireSpy()) {
    const scanner = mountComponent(qrScannerComponent({
        statePath: `data.${id}`,
        sound: false,
        vibrate: false,
        ...options,
    }), { element: document.querySelector(`[data-field-name="${id}"]`), wire });

    return { scanner, wire };
}

function captureEvents(name) {
    const seen = [];
    const record = (event) => seen.push(event.detail);
    window.addEventListener(name, record);
    cleanups.push(() => window.removeEventListener(name, record));

    return seen;
}

const cleanups = [];

afterEach(() => {
    cleanups.splice(0).forEach((cleanup) => cleanup());
});

const scannerMarkup = (name) => `<div data-field-name="${name}"><input type="text" name="${name}" wire:model="data.${name}"></div>`;

describe('QrScanner on the router', () => {
    it('focused scanner field wins over a listener that registers it', () => {
        document.body.innerHTML = `<form>${scannerMarkup('sku')}<input type="text" name="other"></form><div id="listener"></div>`;
        mountInto('listener', qrHardwareScannerListenerComponent({
            fields: ['other', 'sku'],
            sound: false,
            vibrate: false,
            autoFocusNext: false,
        }));
        const { scanner, wire } = mountScanner('sku');
        const hardware = captureEvents('qr-hardware-scanned');
        document.querySelector('[name="sku"]').focus();

        typeBurst('PRD-1001');

        expect(scanner.value).toBe('PRD-1001');
        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
        expect(document.querySelector('[name="other"]').value).toBe('');
        expect(hardware).toEqual([{ value: 'PRD-1001', field: 'sku' }]);
    });

    it('a checking scanner refuses the burst so it falls through', () => {
        document.body.innerHTML = `${scannerMarkup('sku')}<div id="collector"></div>`;
        const { scanner } = mountScanner('sku');
        const collector = mountInto('collector', qrCollectorComponent({ sound: false, vibrate: false, delayBetweenScansMs: 0 }));
        scanner.isChecking = true;
        document.querySelector('[name="sku"]').focus();

        typeBurst('PRD-1001');

        expect(collector.items.map(({ code }) => code)).toEqual(['PRD-1001']);
    });

    it('scanner events carry mode single', () => {
        document.body.innerHTML = scannerMarkup('sku');
        mountScanner('sku');
        const scanned = captureEvents('qr-scanned');
        document.querySelector('[name="sku"]').focus();

        typeBurst('PRD-1001');

        expect(scanned).toEqual([expect.objectContaining({ value: 'PRD-1001', source: 'hardware', mode: 'single' })]);
    });

    it('open camera modal owns hardware bursts', () => {
        document.body.innerHTML = `${scannerMarkup('first')}${scannerMarkup('second')}`;
        const { scanner: first } = mountScanner('first');
        const { scanner: second } = mountScanner('second');
        second.isModalOpen = true;
        document.querySelector('[name="first"]').focus();

        typeBurst('PRD-1001');

        expect(second.value).toBe('PRD-1001');
        expect(first.value).toBeNull();
    });

    it('does not register when the hardware scanner is disabled', () => {
        document.body.innerHTML = scannerMarkup('sku');
        mountScanner('sku', { hardwareScanner: false });

        expect(inputRouter().isActive()).toBe(false);
    });
});
