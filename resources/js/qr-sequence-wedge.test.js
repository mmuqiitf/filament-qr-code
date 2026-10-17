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
});
