// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createInputRouter } from './input-router.js';

const detection = {
    burstThresholdMs: 50,
    minFastKeys: 1,
    minBarcodeLength: 2,
    terminators: ['Enter', 'Tab'],
    scanTimeoutMs: 150,
    preventSubmit: true,
};

function typeBurst(code, { gapMs = 10, terminator = 'Enter' } = {}) {
    const keys = [...code, ...(terminator ? [terminator] : [])];
    const events = [];

    for (const key of keys) {
        const target = document.activeElement ?? document.body;
        const event = new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true });
        target.dispatchEvent(event);
        events.push(event);

        if (key.length === 1 && target.tagName === 'INPUT' && !event.defaultPrevented) {
            target.value += key;
        }

        vi.advanceTimersByTime(gapMs);
    }

    return events;
}

function makeTarget(overrides = {}) {
    const deliveries = [];

    return {
        deliveries,
        target: {
            id: overrides.id ?? Math.random().toString(36).slice(2),
            kind: 'field',
            scope: 'focus',
            name: null,
            detection,
            deliver: (code, context) => deliveries.push({ code, ...context }),
            ...overrides,
        },
    };
}

let router;

beforeEach(() => {
    vi.useFakeTimers();
    router = createInputRouter();
});

afterEach(() => {
    router.destroy();
    vi.useRealTimers();
    document.body.innerHTML = '';
});

describe('input router: focus', () => {
    it('focused scan field wins over listener fields; one burst handled once', () => {
        document.body.innerHTML = `
            <div id="listener"></div>
            <input id="listened" name="sku">
            <div id="field"><input id="field-input"></div>`;
        const listener = makeTarget({
            kind: 'listener',
            scope: 'page',
            element: document.getElementById('listener'),
            candidates: () => [document.getElementById('listened')],
        });
        const field = makeTarget({ element: document.getElementById('field'), name: 'code' });
        router.register(listener.target);
        router.register(field.target);
        document.getElementById('field-input').focus();

        typeBurst('PRD-1001');

        expect(field.deliveries.map(({ code, reason }) => [code, reason])).toEqual([['PRD-1001', 'focus']]);
        expect(listener.deliveries).toEqual([]);
    });
});
