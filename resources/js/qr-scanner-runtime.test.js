// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { inputRouter } from './input-router.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

const accepted = (code) => ({ passes: true, code, source: 'manual', message: null, record_key: null });

function deferred() {
    let resolve;
    const promise = new Promise((done) => {
        resolve = done;
    });

    return { promise, resolve };
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

const baseOptions = {
    sound: false,
    vibrate: false,
    hardwareScanner: false,
    serverCheck: true,
    cooldownMs: 0,
    successFeedback: { sound: false, vibrate: [], flash: false },
    errorFeedback: { sound: false, vibrate: [], flash: false },
};

function mountScanner({ name = 'sku', respond = (key, method, { code }) => Promise.resolve(accepted(code)), attribute = null, ...options } = {}) {
    const host = document.createElement('div');
    host.setAttribute('data-field-name', name);
    host.setAttribute('data-qr-scanner', '');
    host.innerHTML = `<input type="text" wire:model="data.${name}"><div data-qr-scanner-panel></div>`;

    if (attribute) {
        host.setAttribute('data-qr-options', JSON.stringify(attribute));
    }

    document.body.appendChild(host);

    const wire = createWireSpy({ respond });
    const scanner = mountComponent(qrScannerComponent({
        ...baseOptions,
        statePath: `data.${name}`,
        componentKey: `form.${name}`,
        ...(attribute ?? {}),
        ...options,
    }), { element: host, wire });

    return { scanner, wire, host };
}

const mounted = [];

function track(result) {
    mounted.push(result.scanner);

    return result;
}

afterEach(() => {
    mounted.splice(0).forEach((scanner) => scanner.destroy());
    inputRouter().destroy();
    document.body.innerHTML = '';
});

describe('QrScanner runtime options', () => {
    it('setOptions switches mode without re-creating the scanner', async () => {
        const { scanner, host } = track(mountScanner({ mode: 'single' }));
        const api = host.qrScanner;

        await scanner.handleScanResult('A-1', 'manual');
        expect(scanner.value).toBe('A-1');

        api.setOptions({ mode: 'continuous' });
        await scanner.handleScanResult('A-2', 'manual');

        expect(host.qrScanner).toBe(api);
        expect(api.getOptions().mode).toBe('continuous');
        expect(scanner.value).toBe('');
    });

    it('applies a re-rendered data-qr-options attribute in morph mode', async () => {
        const { host } = track(mountScanner({ attribute: { ...baseOptions, mode: 'single', syncOptions: 'morph' } }));

        host.setAttribute('data-qr-options', JSON.stringify({ ...baseOptions, mode: 'continuous', syncOptions: 'morph' }));
        await flush();

        expect(host.qrScanner.getOptions().mode).toBe('continuous');
    });

    it('waits for a refresh event in refresh mode', async () => {
        const { host } = track(mountScanner({ attribute: { ...baseOptions, pauseOnReject: false, syncOptions: 'refresh' } }));

        host.setAttribute('data-qr-options', JSON.stringify({ ...baseOptions, pauseOnReject: true, syncOptions: 'refresh' }));
        await flush();

        expect(host.qrScanner.getOptions().pauseOnReject).toBe(false);

        window.dispatchEvent(new CustomEvent('qr-scanner-refresh', { detail: { field: 'data.sku' } }));

        expect(host.qrScanner.getOptions().pauseOnReject).toBe(true);
    });

    it('finishes a running check before new options apply', async () => {
        const check = deferred();
        const { scanner, host } = track(mountScanner({ respond: () => check.promise }));

        const running = scanner.handleScanResult('A-1', 'manual');
        host.qrScanner.setOptions({ mode: 'continuous' });

        expect(host.qrScanner.getOptions().mode).toBe('single');

        check.resolve(accepted('A-1'));
        await running;

        // The finished scan used the old options; the next one uses the new.
        expect(scanner.value).toBe('A-1');
        expect(host.qrScanner.getOptions().mode).toBe('continuous');
    });

    it('rebuilds the decoder for new formats on the next camera start', () => {
        const { scanner, host } = track(mountScanner());
        const decoder = { stop: () => Promise.resolve() };

        scanner.html5Qrcode = decoder;
        scanner.isScanning = true;
        host.qrScanner.setOptions({ formats: ['qr_code'] });

        expect(scanner.html5Qrcode).toBe(decoder);
        expect(scanner.decoderIsStale).toBe(true);

        scanner.isScanning = false;
        host.qrScanner.setOptions({ fps: 10 });
        scanner.html5Qrcode = null;
        scanner.decoderIsStale = false;

        scanner.html5Qrcode = decoder;
        host.qrScanner.setOptions({ formats: ['ean_13'] });

        expect(scanner.html5Qrcode).toBeNull();
    });

    it('re-registers with the input router when hardware options change', () => {
        track(mountScanner({ hardwareScanner: true }));
        const host = document.querySelector('[data-field-name="sku"]');

        expect(inputRouter().targetCount()).toBe(1);

        host.qrScanner.setOptions({ hardwareScanner: false });
        expect(inputRouter().targetCount()).toBe(0);

        host.qrScanner.setOptions({ hardwareScanner: true, hardwareScope: 'page' });
        expect(inputRouter().targetCount()).toBe(1);
    });
});

describe('QrScanner control API', () => {
    it('feeds, pauses, resumes and resets through the element', async () => {
        const { scanner, wire, host } = track(mountScanner());
        const api = host.qrScanner;

        await api.feed('A-1');
        expect(wire.methodCalls.at(-1)).toEqual(['form.sku', 'handleScan', { code: 'A-1', source: 'manual' }]);

        api.pause();
        expect(api.getState()).toMatchObject({ isPaused: true, value: 'A-1' });
        expect(await api.feed('A-2')).toMatchObject({ ignored: true });

        api.resume();
        await api.feed('A-3', 'hardware');
        expect(wire.methodCalls.at(-1)[2]).toEqual({ code: 'A-3', source: 'hardware' });

        api.reset();
        expect(api.getState()).toMatchObject({ value: '', isPaused: false, isChecking: false });
        expect(scanner.rejectionMessage).toBe('');
    });

    it('treats an unknown feed source as manual', async () => {
        const { wire, host } = track(mountScanner());

        await host.qrScanner.feed('A-1', 'telepathy');

        expect(wire.methodCalls.at(-1)[2].source).toBe('manual');
    });

    it('answers window events aimed at its state path, name or key', async () => {
        const sku = track(mountScanner({ name: 'sku' }));
        const other = track(mountScanner({ name: 'other' }));

        window.dispatchEvent(new CustomEvent('qr-scanner-pause', { detail: { field: 'data.sku' } }));
        expect(sku.scanner.isPaused).toBe(true);
        expect(other.scanner.isPaused).toBe(false);

        window.dispatchEvent(new CustomEvent('qr-scanner-resume', { detail: { field: 'sku' } }));
        expect(sku.scanner.isPaused).toBe(false);

        window.dispatchEvent(new CustomEvent('qr-scanner-set-options', { detail: { field: 'form.other', options: { pauseOnReject: true } } }));
        expect(other.host.qrScanner.getOptions().pauseOnReject).toBe(true);
        expect(sku.host.qrScanner.getOptions().pauseOnReject).toBe(false);

        window.dispatchEvent(new CustomEvent('qr-scanner-feed', { detail: { field: 'data.other', code: 'B-1' } }));
        await flush();
        expect(other.wire.methodCalls.map(([, , { code }]) => code)).toEqual(['B-1']);
        expect(sku.wire.methodCalls).toEqual([]);

        window.dispatchEvent(new CustomEvent('qr-scanner-pause', { detail: {} }));
        expect([sku.scanner.isPaused, other.scanner.isPaused]).toEqual([true, true]);
    });

    it('stops listening once destroyed', () => {
        const { scanner, host } = mountScanner();
        scanner.destroy();

        window.dispatchEvent(new CustomEvent('qr-scanner-pause', { detail: { field: 'data.sku' } }));

        expect(scanner.isPaused).toBe(false);
        expect(host.qrScanner).toBeUndefined();
    });
});
