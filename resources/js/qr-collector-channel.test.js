import { describe, expect, it, vi, afterEach } from 'vitest';
import qrCollectorComponent from './qr-collector.js';

function makeCollector(overrides = {}) {
    return qrCollectorComponent({
        state: null,
        statePath: null,
        allowDuplicates: true,
        delayBetweenScansMs: 0,
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
    delete globalThis.window;
    delete globalThis.CustomEvent;
});

describe('collector server-notify channel', () => {
    it('prefers the Livewire hook and skips the event when defined', () => {
        const dispatched = stubWindow();
        const collector = makeCollector({ wireScanHandler: true });
        const wire = { handleCollectorScan: vi.fn(), set: vi.fn() };
        collector.$wire = wire;

        collector.handleDetectedCode('PRD-1');

        expect(wire.handleCollectorScan).toHaveBeenCalledTimes(1);
        expect(wire.handleCollectorScan).toHaveBeenCalledWith('PRD-1');
        expect(dispatched).toEqual([]);
    });

    it('uses the window event with a real Livewire $wire proxy when the host has no hook (#35)', () => {
        const dispatched = stubWindow();
        const collector = makeCollector();
        const calls = [];
        // Livewire's $wire answers every property with a callable proxy.
        collector.$wire = new Proxy({}, { get: (_, name) => (...args) => calls.push([name, ...args]) });

        collector.handleDetectedCode('PRD-3');

        expect(calls.map(([name]) => name)).not.toContain('handleCollectorScan');
        expect(dispatched.map((event) => event.type)).toEqual(['qr-collector-item-added']);
    });

    it('falls back to the window event when no Livewire hook exists', () => {
        const dispatched = stubWindow();
        const collector = makeCollector();
        collector.$wire = { set: vi.fn() };

        collector.handleDetectedCode('PRD-2');

        expect(dispatched).toHaveLength(1);
        expect(dispatched[0].type).toBe('qr-collector-item-added');
        expect(dispatched[0].detail).toEqual({ code: 'PRD-2' });
    });

    it('still syncs entangled state on either channel', () => {
        stubWindow();
        const withHook = makeCollector({ statePath: 'data.codes', wireScanHandler: true });
        withHook.$wire = { handleCollectorScan: vi.fn(), set: vi.fn() };
        withHook.handleDetectedCode('A-1');
        expect(withHook.$wire.set).toHaveBeenCalledWith('data.codes', ['A-1']);

        const withEvent = makeCollector({ statePath: 'data.codes' });
        withEvent.$wire = { set: vi.fn() };
        withEvent.handleDetectedCode('B-1');
        expect(withEvent.$wire.set).toHaveBeenCalledWith('data.codes', ['B-1']);
    });

    it('asks item rules before adding a code, and skips a rejection', async () => {
        const dispatched = stubWindow();
        const accepted = makeCollector({
            hasItemRules: true,
            componentKey: 'form.codes',
            wireScanHandler: true,
            delayBetweenScansMs: 0,
        });
        const wire = {
            handleCollectorScan: vi.fn(),
            set: vi.fn(),
            callSchemaComponentMethod: vi.fn().mockResolvedValue({ passes: true, message: null }),
        };
        accepted.$wire = wire;

        await accepted.handleDetectedCode('PRD-1');

        expect(wire.callSchemaComponentMethod).toHaveBeenCalledWith('form.codes', 'checkCollectorItem', { code: 'PRD-1' });
        expect(wire.handleCollectorScan).toHaveBeenCalledTimes(1);
        expect(wire.handleCollectorScan).toHaveBeenCalledWith('PRD-1');
        expect(accepted.items).toHaveLength(1);
        expect(dispatched).toEqual([]);

        const normalized = makeCollector({
            hasItemRules: true,
            componentKey: 'form.codes',
            wireScanHandler: true,
            delayBetweenScansMs: 0,
        });
        const normalizedWire = {
            handleCollectorScan: vi.fn(),
            set: vi.fn(),
            callSchemaComponentMethod: vi.fn().mockResolvedValue({
                passes: true,
                message: null,
                code: '(01)09506000134352(17)261231',
            }),
        };
        normalized.$wire = normalizedWire;

        await normalized.handleDetectedCode('010950600013435217261231');

        expect(normalizedWire.callSchemaComponentMethod).toHaveBeenCalledWith(
            'form.codes',
            'checkCollectorItem',
            { code: '010950600013435217261231' },
        );
        expect(normalizedWire.handleCollectorScan).toHaveBeenCalledWith('(01)09506000134352(17)261231');
        expect(normalized.items[0].code).toBe('(01)09506000134352(17)261231');

        const rejected = makeCollector({
            hasItemRules: true,
            componentKey: 'form.codes',
            wireScanHandler: true,
            delayBetweenScansMs: 0,
        });
        const rejectWire = {
            handleCollectorScan: vi.fn(),
            set: vi.fn(),
            callSchemaComponentMethod: vi.fn().mockResolvedValue({ passes: false, message: 'Unknown code' }),
        };
        rejected.$wire = rejectWire;

        await rejected.handleDetectedCode('NOPE');

        expect(rejectWire.handleCollectorScan).not.toHaveBeenCalled();
        expect(rejected.items).toHaveLength(0);
        expect(dispatched).toEqual([]);
    });

    it('rejects an item-rules scan that cannot reach the server', async () => {
        const dispatched = stubWindow();
        const collector = makeCollector({ hasItemRules: true, wireScanHandler: true, delayBetweenScansMs: 0 });
        const wire = { handleCollectorScan: vi.fn(), set: vi.fn(), callSchemaComponentMethod: vi.fn() };
        collector.$wire = wire;

        await collector.handleDetectedCode('PRD-1');

        expect(wire.callSchemaComponentMethod).not.toHaveBeenCalled();
        expect(wire.handleCollectorScan).not.toHaveBeenCalled();
        expect(collector.items).toHaveLength(0);
        expect(dispatched).toEqual([]);
    });

    it('throttles bursts so one scan yields one delivery', () => {
        stubWindow();
        const collector = makeCollector({ delayBetweenScansMs: 1200, wireScanHandler: true });
        const wire = { handleCollectorScan: vi.fn(), set: vi.fn() };
        collector.$wire = wire;

        collector.handleDetectedCode('PRD-3');
        collector.handleDetectedCode('PRD-3');

        expect(wire.handleCollectorScan).toHaveBeenCalledTimes(1);
        expect(collector.items).toHaveLength(1);
    });
});

describe('collector normalizeWith presets', () => {
    it('applies the presets before duplicates are checked and the code is added', () => {
        const dispatched = stubWindow();
        const collector = makeCollector({
            allowDuplicates: false,
            normalize: { trim: true, uppercase: true, stripPrefixes: [']C1'] },
        });

        collector.handleDetectedCode(' ]C1sku-1 ');
        collector.handleDetectedCode('sku-1');

        expect(collector.items).toHaveLength(1);
        expect(collector.items[0].code).toBe('SKU-1');
        expect(dispatched).toHaveLength(1);
        expect(dispatched[0].detail).toEqual({ code: 'SKU-1' });
    });
});
