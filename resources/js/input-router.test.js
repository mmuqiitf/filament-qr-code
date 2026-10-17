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

describe('input router: first listening target', () => {
    it('routes to the first page target in DOM order when nothing is focused', () => {
        document.body.innerHTML = '<div id="a"></div><div id="b"></div>';
        const second = makeTarget({ scope: 'page', element: document.getElementById('b') });
        const first = makeTarget({ scope: 'page', element: document.getElementById('a') });
        router.register(second.target);
        router.register(first.target);

        typeBurst('PRD-1001');

        expect(first.deliveries.map(({ code, reason }) => [code, reason])).toEqual([['PRD-1001', 'first-listening']]);
        expect(second.deliveries).toEqual([]);
    });

    it('ranks listener candidates by each input\'s DOM position, not the listener element', () => {
        document.body.innerHTML = '<div id="field"></div><input id="sku"><div id="listener"></div>';
        const listener = makeTarget({
            kind: 'listener',
            scope: 'page',
            element: document.getElementById('listener'),
            candidates: () => [document.getElementById('sku')],
        });
        const field = makeTarget({ scope: 'page', element: document.getElementById('field') });
        document.getElementById('field').remove();
        document.body.appendChild(field.target.element);
        router.register(field.target);
        router.register(listener.target);

        typeBurst('PRD-1001');

        expect(listener.deliveries.map(({ code, element }) => [code, element.id])).toEqual([['PRD-1001', 'sku']]);
        expect(field.deliveries).toEqual([]);
    });

    it('skips candidates that sit inside another registered target', () => {
        document.body.innerHTML = '<div id="field"><input id="inner"></div><input id="other"><div id="listener"></div>';
        const field = makeTarget({ scope: 'focus', element: document.getElementById('field') });
        const listener = makeTarget({
            kind: 'listener',
            scope: 'page',
            element: document.getElementById('listener'),
            candidates: () => [document.getElementById('inner'), document.getElementById('other')],
        });
        router.register(field.target);
        router.register(listener.target);

        typeBurst('PRD-1001');

        expect(listener.deliveries.map(({ element }) => element.id)).toEqual(['other']);
        expect(field.deliveries).toEqual([]);
    });

    it('skips targets whose accept() returns false', () => {
        document.body.innerHTML = '<div id="a"></div><div id="b"></div>';
        const busy = makeTarget({ scope: 'page', element: document.getElementById('a'), accept: () => false });
        const free = makeTarget({ scope: 'page', element: document.getElementById('b') });
        router.register(busy.target);
        router.register(free.target);

        typeBurst('PRD-1001');

        expect(busy.deliveries).toEqual([]);
        expect(free.deliveries).toHaveLength(1);
    });

    it('ignores focus-scope targets that are not focused', () => {
        document.body.innerHTML = '<div id="a"><input></div><div id="b"></div>';
        const focusOnly = makeTarget({ element: document.getElementById('a') });
        const page = makeTarget({ scope: 'page', element: document.getElementById('b') });
        router.register(focusOnly.target);
        router.register(page.target);

        typeBurst('PRD-1001');

        expect(focusOnly.deliveries).toEqual([]);
        expect(page.deliveries).toHaveLength(1);
    });

    it('uses the fallback target when no candidate matches', () => {
        document.body.innerHTML = '<div id="listener"></div>';
        const listener = makeTarget({
            kind: 'listener',
            scope: 'page',
            element: document.getElementById('listener'),
            candidates: () => [],
            fallback: true,
        });
        router.register(listener.target);

        typeBurst('PRD-1001');

        expect(listener.deliveries.map(({ code, reason }) => [code, reason])).toEqual([['PRD-1001', 'fallback']]);
    });

    it('dispatches qr-scan-unrouted when nothing can take the burst', () => {
        document.body.innerHTML = '<div id="a"><input></div>';
        const unrouted = [];
        window.addEventListener('qr-scan-unrouted', (event) => unrouted.push(event.detail), { once: true });
        router.register(makeTarget({ element: document.getElementById('a') }).target);

        typeBurst('PRD-1001');

        expect(unrouted).toEqual([{ value: 'PRD-1001' }]);
    });
});
