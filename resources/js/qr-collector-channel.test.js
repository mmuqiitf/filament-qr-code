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
        const collector = makeCollector();
        const wire = { handleCollectorScan: vi.fn(), set: vi.fn() };
        collector.$wire = wire;

        collector.handleDetectedCode('PRD-1');

        expect(wire.handleCollectorScan).toHaveBeenCalledTimes(1);
        expect(wire.handleCollectorScan).toHaveBeenCalledWith('PRD-1');
        expect(dispatched).toEqual([]);
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
        const withHook = makeCollector({ statePath: 'data.codes' });
        withHook.$wire = { handleCollectorScan: vi.fn(), set: vi.fn() };
        withHook.handleDetectedCode('A-1');
        expect(withHook.$wire.set).toHaveBeenCalledWith('data.codes', ['A-1']);

        const withEvent = makeCollector({ statePath: 'data.codes' });
        withEvent.$wire = { set: vi.fn() };
        withEvent.handleDetectedCode('B-1');
        expect(withEvent.$wire.set).toHaveBeenCalledWith('data.codes', ['B-1']);
    });

    it('throttles bursts so one scan yields one delivery', () => {
        stubWindow();
        const collector = makeCollector({ delayBetweenScansMs: 1200 });
        const wire = { handleCollectorScan: vi.fn(), set: vi.fn() };
        collector.$wire = wire;

        collector.handleDetectedCode('PRD-3');
        collector.handleDetectedCode('PRD-3');

        expect(wire.handleCollectorScan).toHaveBeenCalledTimes(1);
        expect(collector.items).toHaveLength(1);
    });
});
