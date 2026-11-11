// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { createHardwareScannerHandler, qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import { inputRouter } from './input-router.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

const GS = '\x1D';

function keyEvent(key, extra = {}) {
    return {
        key,
        code: '',
        isComposing: false,
        ctrlKey: false,
        altKey: false,
        metaKey: false,
        preventDefault() {},
        stopPropagation() {},
        ...extra,
    };
}

function typeBurst(handler, text) {
    for (const ch of text) {
        handler.handleKeyDown(keyEvent(ch));
    }
}

const seenListeners = [];

function captureWindowEvent(name) {
    const seen = [];
    const listener = (event) => seen.push(event.detail);
    window.addEventListener(name, listener);
    seenListeners.push([name, listener]);

    return seen;
}

afterEach(() => {
    inputRouter().destroy();
    document.body.innerHTML = '';
    seenListeners.splice(0).forEach(([name, listener]) => window.removeEventListener(name, listener));
});

describe('keyboard-wedge group separator', () => {
    it('keeps a GS character (ASCII 29) typed inside a burst', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        typeBurst(handler, `]C10109506000134352${GS}10ABC`);
        handler.handleKeyDown(keyEvent('Enter'));

        expect(seen).toEqual([`]C10109506000134352${GS}10ABC`]);
    });

    it('reads Ctrl+] inside a burst as the group separator', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        typeBurst(handler, '10ABC');
        handler.handleKeyDown(keyEvent(']', { ctrlKey: true, code: 'BracketRight' }));
        typeBurst(handler, '3103001250');
        handler.handleKeyDown(keyEvent('Enter'));

        expect(seen).toEqual([`10ABC${GS}3103001250`]);
    });

    it('ignores Ctrl+] outside a burst, like any shortcut', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        handler.handleKeyDown(keyEvent(']', { ctrlKey: true, code: 'BracketRight' }));

        expect(handler.getBuffer()).toBe('');
        expect(seen).toEqual([]);
    });
});

describe('QrScanner with gs1()', () => {
    function mountGs1Scanner(respond) {
        document.body.innerHTML = '<div data-field-name="label"><input type="text" wire:model="data.label"></div>';
        const element = document.querySelector('[data-field-name="label"]');
        const wire = createWireSpy({ respond });

        return mountComponent(
            qrScannerComponent({
                statePath: 'data.label',
                sound: false,
                vibrate: false,
                hardwareScanner: false,
                serverCheck: true,
                componentKey: 'form.label',
                cooldownMs: 0,
                gs1: true,
            }),
            { element, wire },
        );
    }

    it('leaves a server GS1 rejection to the field error in single mode and never announces it as accepted', async () => {
        const scanner = mountGs1Scanner(() => Promise.resolve({
            passes: false, code: '(01)09506000134353', source: 'hardware', message: 'Invalid check digit in AI (01)', record_key: null,
        }));
        const accepted = captureWindowEvent('qr-scan-accepted');
        const scanned = captureWindowEvent('qr-scanned');
        const rejected = captureWindowEvent('qr-scan-rejected');

        await scanner.handleScanResult('(01)09506000134353', 'hardware');

        // The server put the reason in the error bag; the status line would repeat it.
        expect(scanner.rejectionMessage).toBe('');
        expect(rejected.map((detail) => detail.message)).toEqual(['Invalid check digit in AI (01)']);
        expect(accepted).toEqual([]);
        expect(scanned).toEqual([]);
    });

    it('shows inline why a GS1 scan could not be checked', async () => {
        const scanner = mountGs1Scanner(() => Promise.reject(new Error('offline')));
        const originalError = console.error;
        console.error = () => {};

        try {
            await scanner.handleScanResult('(01)09506000134352', 'hardware');
        } finally {
            console.error = originalError;
        }

        expect(scanner.rejectionMessage).toBe('The scan could not be checked. Please try again.');
    });

    it('shows the normalized bracketed string the server stored', async () => {
        const scanner = mountGs1Scanner(() => Promise.resolve({
            passes: true, code: '(01)09506000134352(10)ABC', source: 'hardware', message: null, record_key: null,
        }));

        await scanner.handleScanResult(`0109506000134352${GS}10ABC`, 'hardware');

        expect(scanner.value).toBe('(01)09506000134352(10)ABC');
        expect(scanner.rejectionMessage).toBe('');
    });
});

describe('QrHardwareScannerListener with gs1()', () => {
    function mountGs1Listener(respond, options = {}) {
        const element = document.createElement('div');
        document.body.appendChild(element);
        const wire = createWireSpy({ respond });
        const listener = mountComponent(
            qrHardwareScannerListenerComponent({
                sound: false, vibrate: false, autoFocusNext: false, gs1: true, componentKey: 'form.listener', ...options,
            }),
            { element, wire },
        );

        return { listener, wire };
    }

    it('sends the burst to the server instead of typing it into an input', async () => {
        document.body.innerHTML = '<form><input type="text" name="gtin"></form>';
        const { listener, wire } = mountGs1Listener(() => Promise.resolve({
            passes: true, code: '(01)09506000134352(10)ABC', message: null,
        }));
        const scanned = captureWindowEvent('qr-hardware-scanned');

        await listener.handleGlobalScan(`0109506000134352${GS}10ABC`);

        expect(wire.methodCalls).toEqual([['form.listener', 'handleGs1Scan', { code: `0109506000134352${GS}10ABC` }]]);
        expect(document.querySelector('[name="gtin"]').value).toBe('');
        expect(scanned).toEqual([{ value: '(01)09506000134352(10)ABC', field: null, gs1: true }]);
        expect(listener.rejectionMessage).toBe('');
    });

    it('shows a rejection inline and dispatches qr-scan-rejected without qr-hardware-scanned', async () => {
        const { listener } = mountGs1Listener(() => Promise.resolve({
            passes: false, code: '4006381333931', message: 'Plain product barcode, not a GS1 label',
        }));
        const rejected = captureWindowEvent('qr-scan-rejected');
        const scanned = captureWindowEvent('qr-hardware-scanned');

        await listener.handleGlobalScan('4006381333931');

        expect(listener.rejectionMessage).toBe('Plain product barcode, not a GS1 label');
        expect(rejected).toEqual([{ value: '4006381333931', field: null, source: 'hardware', message: 'Plain product barcode, not a GS1 label' }]);
        expect(scanned).toEqual([]);
    });

    it('owns page bursts through the router even with no empty registered input', async () => {
        const { wire } = mountGs1Listener(() => Promise.resolve({ passes: true, code: '(01)09506000134352', message: null }));
        const router = inputRouter();
        const now = Date.now;
        let clock = 1000;
        Date.now = () => clock;

        try {
            for (const ch of '0109506000134352') {
                clock += 5;
                window.dispatchEvent(new KeyboardEvent('keydown', { key: ch }));
            }
            clock += 5;
            window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }));
        } finally {
            Date.now = now;
        }

        expect(router.isActive()).toBe(true);
        expect(wire.methodCalls.map(([, method, args]) => [method, args.code])).toEqual([['handleGs1Scan', '0109506000134352']]);
    });
});
