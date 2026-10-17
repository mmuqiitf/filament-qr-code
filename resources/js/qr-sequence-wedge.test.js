// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { inputRouter } from './input-router.js';
import qrScanSequenceComponent from './qr-sequence.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: () => Promise.reject(new Error('no decoder in tests')),
}));

const fields = [{ key: 'po', label: 'PO' }, { key: 'sku', label: 'SKU' }, { key: 'lot', label: 'Lot' }];
const order = ['po', 'sku', 'lot'];

function typeBurst(code) {
    for (const key of [...code, 'Enter']) {
        const target = document.activeElement ?? document.body;
        target.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
        vi.advanceTimersByTime(10);
    }
}

async function settle() {
    await vi.advanceTimersByTimeAsync(0);
}

// A stand-in for handleStepScan: sticky po, continuous rounds.
function fakeServer() {
    let values = {};

    return (key, method, { step, code, source }) => {
        values = { ...values, [step]: code };
        const next = order.find((candidate) => !values[candidate]) ?? null;

        if (next !== null) {
            return { passes: true, code, source, step, skipped: [], next_step: next, completed: false, reset: false, values: { ...values } };
        }

        values = { po: values.po };

        return { passes: true, code, source, step, skipped: [], next_step: 'sku', completed: true, submitted: null, reset: true, values: { ...values } };
    };
}

function mountSequence(respond, options = {}) {
    document.body.innerHTML = '<div id="sequence"></div>';
    const wire = createWireSpy({ respond });
    const sequence = mountComponent(qrScanSequenceComponent({
        fields,
        sound: false,
        vibrate: false,
        serverCheck: true,
        componentKey: 'form.receipt',
        componentStatePath: 'data.receipt',
        ...options,
    }), { element: document.getElementById('sequence'), wire });

    return { wire, sequence };
}

beforeEach(() => {
    vi.useFakeTimers({ now: 1_000_000 });
});

afterEach(() => {
    inputRouter().destroy();
    vi.useRealTimers();
    document.body.innerHTML = '';
});

describe('wedge-driven sequences', () => {
    it('three bursts fill steps po, sku, lot in order; sticky po survives reset', async () => {
        const steps = [];
        window.addEventListener('qr-sequence-step', (event) => steps.push(event.detail));
        const { wire, sequence } = mountSequence(fakeServer());

        for (const code of ['PO-1', 'SKU-1', 'L001']) {
            typeBurst(code);
            await settle();
        }

        expect(wire.methodCalls.map(([, , args]) => [args.step, args.code, args.source])).toEqual([
            ['po', 'PO-1', 'hardware'],
            ['sku', 'SKU-1', 'hardware'],
            ['lot', 'L001', 'hardware'],
        ]);
        expect(steps.map((detail) => [detail.field, detail.source])).toEqual([['po', 'hardware'], ['sku', 'hardware'], ['lot', 'hardware']]);
        expect(sequence.results).toEqual({ po: 'PO-1' });
        expect(sequence.getCurrentField().key).toBe('sku');
    });

    it('gives a burst to the step whose input has focus', async () => {
        const { wire, sequence } = mountSequence(fakeServer());
        const input = document.createElement('input');
        input.setAttribute('data-qr-step', 'lot');
        document.getElementById('sequence').appendChild(input);
        input.focus();

        typeBurst('L009');
        await settle();

        expect(wire.methodCalls[0][2]).toMatchObject({ step: 'lot', code: 'L009' });
        expect(input.value).toBe('');
        expect(sequence.results.lot).toBe('L009');
    });

    it('holds one burst while a check is in flight and replays it', async () => {
        let release;
        const pending = [];
        const server = fakeServer();
        const { wire } = mountSequence((key, method, args) => {
            pending.push(args);

            return pending.length === 1
                ? new Promise((resolve) => { release = () => resolve(server(key, method, args)); })
                : server(key, method, args);
        });
        const dropped = [];
        window.addEventListener('qr-scan-dropped', (event) => dropped.push(event.detail.value));

        typeBurst('PO-1');
        typeBurst('SKU-1');
        typeBurst('SKU-2');
        expect(wire.methodCalls).toHaveLength(1);

        release();
        await settle();
        await settle();

        expect(wire.methodCalls.map(([, , args]) => [args.step, args.code])).toEqual([['po', 'PO-1'], ['sku', 'SKU-1']]);
    });

    it('keeps the local path for plain sequences', async () => {
        const { wire, sequence } = mountSequence(() => null, { serverCheck: false });

        typeBurst('PO-1');
        typeBurst('SKU-1');
        await settle();

        expect(wire.methodCalls).toEqual([]);
        expect(sequence.results).toEqual({ po: 'PO-1', sku: 'SKU-1' });
    });

    it('shows repeat progress and finishes a step with the Done control', async () => {
        const events = [];
        window.addEventListener('qr-sequence-completed', (event) => events.push(event.detail));
        const { wire, sequence } = mountSequence((key, method, args) => (method === 'finishStep'
            ? { passes: true, code: '', source: 'manual', step: 'sku', skipped: [], next_step: null, completed: true, submitted: true, reset: true, finished: true, values: { po: 'PO-1' } }
            : { passes: true, code: args.code, source: 'hardware', step: 'sku', skipped: [], next_step: 'sku', completed: false, reset: false, values: { po: 'PO-1', sku: ['A1', args.code] } }), {
            steps: [
                { key: 'po', optional: false, sticky: true, repeat: null, repeating: false },
                { key: 'sku', optional: false, sticky: false, repeat: 3, repeating: true },
                { key: 'lot', optional: false, sticky: false, repeat: null, repeating: false },
            ],
        });
        sequence.results = { po: 'PO-1', sku: ['A1'] };
        sequence.setCurrentField(1);

        expect(sequence.stepProgress('sku')).toBe('1 / 3');
        await sequence.processScan('A2', 'hardware');
        expect(sequence.stepProgress('sku')).toBe('2 / 3');
        expect(sequence.isRepeating('sku')).toBe(true);
        expect(sequence.stepProgress('po')).toBe(null);

        await sequence.finishStep('sku');

        expect(wire.methodCalls.at(-1)).toEqual(['form.receipt', 'finishStep', { step: 'sku' }]);
        expect(events).toEqual([{ results: { po: 'PO-1', sku: ['A1', 'A2'] }, submitted: true }]);
        expect(sequence.results).toEqual({ po: 'PO-1' });
    });

    it('shows open-ended progress as a plain count', () => {
        const { sequence } = mountSequence(() => null, {
            steps: [{ key: 'po', optional: false, sticky: false, repeat: null, repeating: true }],
        });
        sequence.results = { po: ['A', 'B'] };

        expect(sequence.stepProgress('po')).toBe('2');
    });
});
