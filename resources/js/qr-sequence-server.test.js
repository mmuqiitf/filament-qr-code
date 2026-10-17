// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { inputRouter } from './input-router.js';
import qrScanSequenceComponent from './qr-sequence.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: () => Promise.reject(new Error('no decoder in tests')),
}));

const fields = [{ key: 'po', label: 'PO' }, { key: 'sku', label: 'SKU' }, { key: 'serial', label: 'Serial' }];

function result(overrides) {
    return {
        passes: true, code: '', source: 'hardware', message: null, record_key: null,
        step: null, skipped: [], next_step: null, completed: false, invalid_step: null,
        submitted: null, reset: false, values: {},
        ...overrides,
    };
}

function mountSequence(respond, options = {}) {
    const wire = createWireSpy({ respond });
    const sequence = mountComponent(qrScanSequenceComponent({
        fields, sound: false, vibrate: false, hardwareScanner: false,
        serverCheck: true, componentKey: 'form.receipt', componentStatePath: 'data.receipt',
        ...options,
    }), { element: document.body, wire });

    return { wire, sequence };
}

function captureEvents(...names) {
    const events = [];
    names.forEach((name) => window.addEventListener(name, (event) => events.push([name, event.detail])));

    return events;
}

afterEach(() => {
    inputRouter().destroy();
    document.body.innerHTML = '';
});

describe('sequence server path', () => {
    it('sends one handleStepScan call per scan and never writes state itself', async () => {
        const { wire, sequence } = mountSequence((key, method, args) => result({
            code: args.code, step: args.step, next_step: 'sku', values: { po: args.code },
        }));

        await sequence.processScan('PO-1', 'hardware');

        expect(wire.methodCalls).toEqual([['form.receipt', 'handleStepScan', { step: 'po', code: 'PO-1', source: 'hardware' }]]);
        expect(wire.calls).toEqual([]);
        expect(sequence.results).toEqual({ po: 'PO-1' });
        expect(sequence.getCurrentField().key).toBe('sku');
    });

    it('stays on a rejected step and reports it', async () => {
        const events = captureEvents('qr-sequence-rejected', 'qr-sequence-step');
        const { sequence } = mountSequence((key, method, args) => result({
            passes: false, code: args.code, step: 'po', next_step: 'po', message: 'Bad PO',
        }));

        await sequence.processScan('nope', 'hardware');

        expect(sequence.getCurrentField().key).toBe('po');
        expect(sequence.stepError).toBe('Bad PO');
        expect(events).toEqual([['qr-sequence-rejected', { field: 'po', value: 'nope', message: 'Bad PO', source: 'hardware' }]]);
    });

    it('jumps to the invalid step after a failed whole-set check', async () => {
        const events = captureEvents('qr-sequence-invalid', 'qr-sequence-completed');
        const { sequence } = mountSequence(() => result({
            passes: false, code: 'SN1', step: 'serial', next_step: 'sku', invalid_step: 'sku',
            message: 'Mismatch', values: { po: 'PO-1', sku: 'SKU-1', serial: 'SN1' },
        }));
        sequence.setCurrentField(2);

        await sequence.processScan('SN1', 'camera');

        expect(sequence.getCurrentField().key).toBe('sku');
        expect(events).toEqual([['qr-sequence-invalid', { field: 'sku', message: 'Mismatch', values: { po: 'PO-1', sku: 'SKU-1', serial: 'SN1' } }]]);
    });

    it('reports completion with the completed values even when the server reset', async () => {
        const events = captureEvents('qr-sequence-completed');
        const { sequence } = mountSequence(() => result({
            code: 'SN1', step: 'serial', completed: true, submitted: true, reset: true, next_step: 'po', values: {},
        }));
        sequence.results = { po: 'PO-1', sku: 'SKU-1' };
        sequence.setCurrentField(2);

        await sequence.processScan('SN1', 'hardware');

        expect(events).toEqual([['qr-sequence-completed', { results: { po: 'PO-1', sku: 'SKU-1', serial: 'SN1' }, submitted: true }]]);
        expect(sequence.results).toEqual({});
        expect(sequence.getCurrentField().key).toBe('po');
    });

    it('marks skipped optional steps', async () => {
        const events = captureEvents('qr-sequence-step');
        const { sequence } = mountSequence(() => result({
            code: 'SN1', step: 'serial', skipped: ['sku'], next_step: null, values: { po: 'PO-1', serial: 'SN1' },
        }));
        sequence.setCurrentField(1);

        await sequence.processScan('SN1', 'hardware');

        expect(events[0]).toEqual(['qr-sequence-step', { field: 'serial', value: 'SN1', index: 2, skipped: ['sku'] }]);
    });

    it('drops a scan while a check is in flight', async () => {
        let release;
        const { wire, sequence } = mountSequence(() => new Promise((resolve) => { release = resolve; }));

        const first = sequence.processScan('PO-1', 'hardware');
        await sequence.processScan('PO-2', 'hardware');
        release(result({ code: 'PO-1', step: 'po', next_step: 'sku', values: { po: 'PO-1' } }));
        await first;

        expect(wire.methodCalls).toHaveLength(1);
    });

    it('treats a failed request as a rejection', async () => {
        const { sequence } = mountSequence(() => { throw new Error('offline'); }, { scanFailedMessage: 'Scan failed' });
        vi.spyOn(console, 'error').mockImplementation(() => {});

        await sequence.processScan('PO-1', 'hardware');

        expect(sequence.stepError).toBe('Scan failed');
        expect(sequence.getCurrentField().key).toBe('po');
    });

    it('keeps the browser-only path without serverCheck', async () => {
        const { wire, sequence } = mountSequence(() => null, { serverCheck: false });

        await sequence.processScan('PO-1', 'hardware');

        expect(wire.methodCalls).toEqual([]);
        expect(wire.calls).toEqual([['data.po', 'PO-1'], ['data.receipt', { po: 'PO-1' }]]);
    });
});
