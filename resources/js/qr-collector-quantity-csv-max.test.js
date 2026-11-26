import { afterEach, describe, expect, it, vi } from 'vitest';
import qrCollectorComponent from './qr-collector.js';

function makeCollector(overrides = {}) {
    return qrCollectorComponent({
        state: null,
        statePath: null,
        allowDuplicates: false,
        delayBetweenScansMs: 0,
        sound: false,
        vibrate: false,
        ...overrides,
    });
}

function stubWindow() {
    const dispatched = [];
    globalThis.window = {
        dispatchEvent: (event) => {
            dispatched.push(event);
            return true;
        },
    };
    globalThis.CustomEvent = class CustomEvent {
        constructor(type, options = {}) {
            this.type = type;
            this.detail = options.detail;
        }
    };
    return dispatched;
}

afterEach(() => {
    vi.useRealTimers();
    delete globalThis.window;
    delete globalThis.CustomEvent;
});

describe('collector quantity mode', () => {
    it('increments quantity on rescan instead of rejecting duplicates and syncs object state', () => {
        const dispatched = stubWindow();
        const collector = makeCollector({
            statePath: 'data.codes',
            quantity: true,
            allowDuplicates: false,
        });
        collector.$wire = { set: vi.fn() };

        collector.handleDetectedCode('PRD-1');
        collector.handleDetectedCode('PRD-1');
        collector.handleDetectedCode('PRD-2');

        expect(collector.items).toHaveLength(2);
        expect(collector.items[0]).toMatchObject({ code: 'PRD-2', qty: 1 });
        expect(collector.items[1]).toMatchObject({ code: 'PRD-1', qty: 2 });
        expect(collector.totalQuantity()).toBe(3);
        expect(collector.$wire.set).toHaveBeenLastCalledWith('data.codes', [
            { code: 'PRD-2', qty: 1 },
            { code: 'PRD-1', qty: 2 },
        ]);
        expect(dispatched).toHaveLength(3);
    });

    it('lets operators increment, decrement, and edit quantity directly', () => {
        stubWindow();
        const collector = makeCollector({
            state: [{ code: 'PRD-1', qty: 2 }],
            statePath: 'data.codes',
            quantity: true,
        });
        collector.$wire = { set: vi.fn() };

        collector.incrementQty(0);
        expect(collector.items[0].qty).toBe(3);

        collector.decrementQty(0);
        collector.decrementQty(0);
        collector.decrementQty(0);
        expect(collector.items[0].qty).toBe(1);

        collector.setQty(0, '7');
        expect(collector.items[0].qty).toBe(7);

        collector.setQty(0, '-3');
        expect(collector.items[0].qty).toBe(1);
        expect(collector.$wire.set).toHaveBeenLastCalledWith('data.codes', [{ code: 'PRD-1', qty: 1 }]);
    });

    it('increments existing normalized item after server checkCollectorItem passes', async () => {
        stubWindow();
        const collector = makeCollector({
            statePath: 'data.codes',
            quantity: true,
            hasItemRules: true,
            componentKey: 'form.codes',
        });
        collector.$wire = {
            set: vi.fn(),
            callSchemaComponentMethod: vi.fn().mockResolvedValue({
                passes: true,
                message: null,
                code: '(01)09506000134352',
            }),
        };

        await collector.handleDetectedCode('0109506000134352');
        await collector.handleDetectedCode('0109506000134352');

        expect(collector.items).toHaveLength(1);
        expect(collector.items[0]).toMatchObject({ code: '(01)09506000134352', qty: 2 });
    });
});

describe('collector maxItems limit', () => {
    it('rejects codes beyond maxItems in standard mode', () => {
        vi.useFakeTimers();
        stubWindow();
        const collector = makeCollector({
            statePath: 'data.codes',
            maxItems: 2,
            maxItemsMessage: 'Maximum of :max items reached.',
        });
        collector.$wire = { set: vi.fn() };

        collector.handleDetectedCode('A-1');
        collector.handleDetectedCode('A-2');
        vi.advanceTimersByTime(600);
        collector.handleDetectedCode('A-3');
        vi.advanceTimersByTime(600);

        expect(collector.items.map(i => i.code)).toEqual(['A-2', 'A-1']);
        expect(collector.announcement).toContain('Maximum of 2 items reached.');
    });

    it('still allows incrementing an existing item at maxItems in quantity mode, while rejecting new codes', () => {
        vi.useFakeTimers();
        stubWindow();
        const collector = makeCollector({
            statePath: 'data.codes',
            quantity: true,
            maxItems: 2,
            maxItemsMessage: 'Maximum of :max items reached.',
        });
        collector.$wire = { set: vi.fn() };

        collector.handleDetectedCode('A-1');
        collector.handleDetectedCode('A-2');
        collector.handleDetectedCode('A-1');
        vi.advanceTimersByTime(600);
        collector.handleDetectedCode('A-3');
        vi.advanceTimersByTime(600);

        expect(collector.items).toHaveLength(2);
        expect(collector.items.find(i => i.code === 'A-1').qty).toBe(2);
        expect(collector.announcement).toContain('Maximum of 2 items reached.');
    });
});

describe('collector CSV import and export', () => {
    it('builds CSV with or without qty column and RFC 4180 quoting', () => {
        const plain = makeCollector({ state: ['PRD-1', 'PRD, "2"'], csvExport: true });
        expect(plain.buildCsv()).toBe('code\nPRD-1\n"PRD, ""2"""\n');

        const qty = makeCollector({
            state: [{ code: 'PRD-1', qty: 4 }, { code: 'PRD,2', qty: 2 }],
            quantity: true,
            csvExport: true,
        });
        expect(qty.buildCsv()).toBe('code,qty\nPRD-1,4\n"PRD,2",2\n');
    });

    it('imports CSV via the server component method and records the report', async () => {
        stubWindow();
        const collector = makeCollector({
            statePath: 'data.codes',
            quantity: true,
            csvImport: true,
            componentKey: 'form.codes',
        });
        collector.$wire = {
            set: vi.fn(),
            callSchemaComponentMethod: vi.fn().mockResolvedValue({
                items: [{ code: 'PRD-1', qty: 3 }],
                added: 1,
                rejected: [{ row: 3, code: 'BAD', reason: 'Invalid format' }],
            }),
        };

        await collector.importCsvText('code,qty\nPRD-1,3\nBAD,1\n');

        expect(collector.$wire.callSchemaComponentMethod).toHaveBeenCalledWith(
            'form.codes',
            'importCollectorCsv',
            { csv: 'code,qty\nPRD-1,3\nBAD,1\n', existing: [] },
        );
        expect(collector.items).toHaveLength(1);
        expect(collector.items[0]).toMatchObject({ code: 'PRD-1', qty: 3 });
        expect(collector.importReport).toEqual({
            added: 1,
            rejected: [{ row: 3, code: 'BAD', reason: 'Invalid format' }],
        });
        expect(collector.$wire.set).toHaveBeenLastCalledWith('data.codes', [{ code: 'PRD-1', qty: 3 }]);
    });
});
