// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { inputRouter } from './input-router.js';
import qrScanSequenceComponent from './qr-sequence.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: () => Promise.reject(new Error('no decoder in tests')),
}));

const fields = [{ key: 'po', label: 'PO' }, { key: 'sku', label: 'SKU' }, { key: 'serials', label: 'Serials' }];

const steps = (repeat = 2, overrides = {}) => [
    { key: 'po', optional: false, sticky: true, repeating: false, repeat: null, active: true, ...(overrides.po ?? {}) },
    { key: 'sku', optional: false, sticky: false, repeating: false, repeat: null, active: true, ...(overrides.sku ?? {}) },
    { key: 'serials', optional: false, sticky: false, repeating: true, repeat, active: true, ...(overrides.serials ?? {}) },
];

const basePayload = (overrides = {}) => ({
    fields, steps: steps(), values: {}, sound: false, vibrate: false, hardwareScanner: false,
    serverCheck: true, componentKey: 'form.receipt', componentStatePath: 'data.receipt', syncOptions: 'morph',
    ...overrides,
});

function result(overrides) {
    return {
        passes: true, code: '', source: 'hardware', message: null, record_key: null,
        step: null, skipped: [], next_step: null, completed: false, invalid_step: null,
        submitted: null, reset: false, values: {},
        ...overrides,
    };
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));
const mounted = [];

function mountSequence({ respond = () => Promise.resolve(result({})), payload = basePayload() } = {}) {
    const host = document.createElement('div');
    host.setAttribute('data-qr-options', JSON.stringify(payload));
    host.innerHTML = '<div data-qr-sequence></div>';
    document.body.appendChild(host);

    const element = host.querySelector('[data-qr-sequence]');
    const wire = createWireSpy({ respond });
    const sequence = mountComponent(qrScanSequenceComponent(payload), { element, wire });
    mounted.push(sequence);

    const rerender = (changes) => host.setAttribute('data-qr-options', JSON.stringify(basePayload({ ...payload, ...changes })));

    return { sequence, wire, host, element, rerender };
}

afterEach(() => {
    mounted.splice(0).forEach((sequence) => sequence.destroy());
    inputRouter().destroy();
    document.body.innerHTML = '';
});

describe('QrScanSequence runtime options', () => {
    it('changing quantity updates repeat k/n', async () => {
        const { sequence, rerender } = mountSequence({ payload: basePayload({ values: { po: 'PO-1', sku: 'PRD-1', serials: ['SN-1'] } }) });

        expect(sequence.stepProgress('serials')).toBe('1 / 2');

        rerender({ steps: steps(5), values: { po: 'PO-1', sku: 'PRD-1', serials: ['SN-1'] } });
        await flush();

        expect(sequence.stepProgress('serials')).toBe('1 / 5');
    });

    it('starts from the server values and follows them on re-render', async () => {
        const { sequence, rerender } = mountSequence({ payload: basePayload({ values: { po: 'PO-1' } }) });

        expect(sequence.results).toEqual({ po: 'PO-1' });
        expect(sequence.getCurrentField().key).toBe('sku');

        rerender({ values: { po: 'PO-2', sku: 'PRD-1' } });
        await flush();

        expect(sequence.results).toEqual({ po: 'PO-2', sku: 'PRD-1' });
        expect(sequence.getCurrentField().key).toBe('serials');

        rerender({ values: [] });
        await flush();

        expect(sequence.results).toEqual({});
    });

    it('keeps the step structure until the round completes or resets', async () => {
        const { sequence, rerender, element } = mountSequence({ payload: basePayload({ values: { po: 'PO-1', sku: 'PRD-1' } }) });
        const longer = [...fields, { key: 'box', label: 'Box' }];

        rerender({ fields: longer, values: { po: 'PO-1', sku: 'PRD-1' } });
        await flush();

        expect(sequence.fields.map(({ key }) => key)).toEqual(['po', 'sku', 'serials']);

        element.qrScanSequence.reset();

        expect(sequence.fields.map(({ key }) => key)).toEqual(['po', 'sku', 'serials', 'box']);
    });

    it('applies label changes mid-round', async () => {
        const { sequence, rerender } = mountSequence({ payload: basePayload({ values: { po: 'PO-1' } }) });

        rerender({ fields: [{ key: 'po', label: 'Order' }, fields[1], fields[2]], values: { po: 'PO-1' } });
        await flush();

        expect(sequence.fields[0].label).toBe('Order');
    });

    it('waits for a running check before applying options', async () => {
        let finish;
        const { sequence, element } = mountSequence({
            respond: () => new Promise((resolve) => {
                finish = resolve;
            }),
        });

        const running = sequence.processScan('PO-1', 'hardware');
        element.qrScanSequence.setOptions({ steps: steps(4) });

        expect(sequence.stepOptions('serials').repeat).toBe(2);

        finish(result({ step: 'po', code: 'PO-1', next_step: 'sku', values: { po: 'PO-1' } }));
        await running;

        expect(sequence.stepOptions('serials').repeat).toBe(4);
    });

    it('waits for a refresh event in refresh mode', async () => {
        const { sequence, rerender } = mountSequence({ payload: basePayload({ syncOptions: 'refresh' }) });

        rerender({ steps: steps(7), syncOptions: 'refresh' });
        await flush();

        expect(sequence.stepOptions('serials').repeat).toBe(2);

        window.dispatchEvent(new CustomEvent('qr-sequence-refresh', { detail: { field: 'form.receipt' } }));

        expect(sequence.stepOptions('serials').repeat).toBe(7);
    });

    it('skips inactive steps when advancing without the server', async () => {
        const { sequence } = mountSequence({
            payload: basePayload({ serverCheck: false, steps: steps(2, { sku: { active: false } }) }),
        });

        await sequence.processScan('PO-1', 'hardware');

        expect(sequence.getCurrentField().key).toBe('serials');
        expect(sequence.isActive('sku')).toBe(false);
    });
});

describe('QrScanSequence control API', () => {
    it('feeds, moves, pauses and resets through the element', async () => {
        const { sequence, wire, element } = mountSequence({
            respond: (key, method, args) => Promise.resolve(result({ step: args.step, code: args.code, next_step: 'sku', values: { po: args.code } })),
        });
        const api = element.qrScanSequence;

        await api.feed('PO-1');
        expect(wire.methodCalls.at(-1)).toEqual(['form.receipt', 'handleStepScan', { step: 'po', code: 'PO-1', source: 'hardware' }]);

        api.goToStep('serials');
        expect(api.getState().currentStep).toBe('serials');

        api.pause();
        expect(await api.feed('SN-1')).toBeUndefined();
        expect(wire.methodCalls).toHaveLength(1);
        api.resume();

        api.reset({ values: { po: 'PO-9' } });
        expect(api.getState()).toMatchObject({ currentStep: 'sku', values: { po: 'PO-9' }, isPaused: false });
        expect(sequence.stepError).toBeNull();
    });

    it('answers window events aimed at its key or state path', async () => {
        const { sequence, wire } = mountSequence({
            respond: (key, method, args) => Promise.resolve(result({ step: args.step, code: args.code, next_step: 'sku', values: { po: args.code } })),
        });

        window.dispatchEvent(new CustomEvent('qr-sequence-go-to', { detail: { field: 'form.receipt', step: 'sku' } }));
        expect(sequence.getCurrentField().key).toBe('sku');

        window.dispatchEvent(new CustomEvent('qr-sequence-go-to', { detail: { field: 'form.other', step: 'po' } }));
        expect(sequence.getCurrentField().key).toBe('sku');

        window.dispatchEvent(new CustomEvent('qr-sequence-reset', { detail: { field: 'data.receipt', values: { po: 'PO-1' } } }));
        expect(sequence.results).toEqual({ po: 'PO-1' });

        window.dispatchEvent(new CustomEvent('qr-sequence-feed', { detail: { field: 'form.receipt', code: 'PRD-1' } }));
        await flush();
        expect(wire.methodCalls.at(-1)[2]).toMatchObject({ step: 'sku', code: 'PRD-1' });

        window.dispatchEvent(new CustomEvent('qr-sequence-set-repeat', { detail: { field: 'form.receipt', step: 'serials', count: 9 } }));
        expect(sequence.stepProgress('serials')).toBe('0 / 9');

        window.dispatchEvent(new CustomEvent('qr-sequence-pause', { detail: {} }));
        expect(sequence.isPaused).toBe(true);
        window.dispatchEvent(new CustomEvent('qr-sequence-resume', { detail: { field: 'form.receipt' } }));
        expect(sequence.isPaused).toBe(false);
    });

    it('ignores a go-to for an unknown step', () => {
        const { sequence, element } = mountSequence();

        element.qrScanSequence.goToStep('nope');

        expect(sequence.getCurrentField().key).toBe('po');
    });
});
