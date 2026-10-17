// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createInputRouter, inputRouter } from './input-router.js';

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

describe('input router: modal, events and stray text', () => {
    it('a target with an open camera modal wins over the focused field', () => {
        document.body.innerHTML = '<div id="modal-field"></div><div id="field"><input id="field-input"></div>';
        const modal = makeTarget({ element: document.getElementById('modal-field'), isModalOpen: () => true });
        const field = makeTarget({ element: document.getElementById('field') });
        router.register(field.target);
        router.register(modal.target);
        document.getElementById('field-input').focus();

        typeBurst('PRD-1001');

        expect(modal.deliveries.map(({ reason }) => reason)).toEqual(['modal']);
        expect(field.deliveries).toEqual([]);
    });

    it('dispatches qr-scan-routed with value, target, kind, reason and burstId', () => {
        document.body.innerHTML = '<div id="a"></div>';
        const routed = [];
        const record = (event) => routed.push(event.detail);
        window.addEventListener('qr-scan-routed', record);
        const page = makeTarget({ scope: 'page', name: 'sku', element: document.getElementById('a') });
        router.register(page.target);

        typeBurst('PRD-1001');
        typeBurst('PRD-1002');
        window.removeEventListener('qr-scan-routed', record);

        expect(routed).toEqual([
            { value: 'PRD-1001', target: 'sku', kind: 'field', reason: 'first-listening', burstId: 1 },
            { value: 'PRD-1002', target: 'sku', kind: 'field', reason: 'first-listening', burstId: 2 },
        ]);
        expect(page.deliveries.map(({ burstId }) => burstId)).toEqual([1, 2]);
    });

    it('restores text typed into a focused search input when the burst goes elsewhere', () => {
        document.body.innerHTML = '<input id="search" value="milk"><div id="a"></div>';
        const page = makeTarget({ scope: 'page', element: document.getElementById('a') });
        router.register(page.target);
        const search = document.getElementById('search');
        const inputs = [];
        search.addEventListener('input', () => inputs.push(search.value));
        search.focus();

        typeBurst('PRD-1001');

        expect(search.value).toBe('milk');
        expect(inputs).toEqual(['milk']);
        expect(page.deliveries).toHaveLength(1);
    });

    it('leaves text in an input marked data-qr-ignore', () => {
        document.body.innerHTML = '<div data-qr-ignore><input id="notes"></div><div id="a"></div>';
        const page = makeTarget({ scope: 'page', element: document.getElementById('a') });
        router.register(page.target);
        document.getElementById('notes').focus();

        typeBurst('PRD-1001');

        expect(document.getElementById('notes').value).toBe('PRD-1001');
        expect(page.deliveries).toHaveLength(1);
    });

    it('does not restore when the burst goes to the focused target', () => {
        document.body.innerHTML = '<div id="field"><input id="field-input"></div>';
        const field = makeTarget({ element: document.getElementById('field') });
        router.register(field.target);
        const input = document.getElementById('field-input');
        input.focus();

        typeBurst('PRD-1001');

        expect(input.value).toBe('PRD-1001');
    });
});

describe('input router: lifecycle and settings', () => {
    it('adds one window keydown listener for the first target and removes it after the last', () => {
        document.body.innerHTML = '<div id="a"></div><div id="b"></div>';
        const add = vi.spyOn(window, 'addEventListener');
        const remove = vi.spyOn(window, 'removeEventListener');
        const keydownCalls = (spy) => spy.mock.calls.filter(([type]) => type === 'keydown');

        const unregisterFirst = router.register(makeTarget({ element: document.getElementById('a') }).target);
        const unregisterSecond = router.register(makeTarget({ element: document.getElementById('b') }).target);
        unregisterFirst();

        expect(keydownCalls(add)).toHaveLength(1);
        expect(keydownCalls(remove)).toHaveLength(0);
        expect(router.isActive()).toBe(true);

        unregisterSecond();

        expect(keydownCalls(remove)).toHaveLength(1);
        expect(router.isActive()).toBe(false);
        add.mockRestore();
        remove.mockRestore();
    });

    it('register is idempotent by id', () => {
        document.body.innerHTML = '<div id="a"></div>';
        const page = makeTarget({ id: 'same', scope: 'page', element: document.getElementById('a') });
        router.register(page.target);
        router.register(page.target);

        typeBurst('PRD-1001');

        expect(router.targetCount()).toBe(1);
        expect(page.deliveries).toHaveLength(1);
    });

    it('inputRouter() returns the same instance for every call', () => {
        expect(inputRouter()).toBe(inputRouter());
    });

    it("uses the focused target's detection settings", () => {
        document.body.innerHTML = '<div id="field"><input id="field-input"></div>';
        const slowGun = makeTarget({
            element: document.getElementById('field'),
            detection: { ...detection, burstThresholdMs: 100 },
        });
        router.register(slowGun.target);
        document.getElementById('field-input').focus();

        typeBurst('PRD-1001', { gapMs: 70 });

        expect(slowGun.deliveries.map(({ code }) => code)).toEqual(['PRD-1001']);
    });

    it('uses the most permissive settings across targets when nothing is focused', () => {
        document.body.innerHTML = '<div id="a"></div><div id="b"></div>';
        const strict = makeTarget({ scope: 'page', element: document.getElementById('a') });
        const slowGun = makeTarget({
            scope: 'page',
            element: document.getElementById('b'),
            detection: { ...detection, burstThresholdMs: 100 },
        });
        router.register(strict.target);
        router.register(slowGun.target);

        typeBurst('PRD-1001', { gapMs: 70 });

        expect(strict.deliveries).toHaveLength(1);
    });

    it("re-checks the chosen target's minimum length", () => {
        document.body.innerHTML = '<div id="field"><input id="field-input"></div>';
        const strict = makeTarget({
            element: document.getElementById('field'),
            detection: { ...detection, minBarcodeLength: 8 },
        });
        router.register(strict.target);
        document.getElementById('field-input').focus();

        typeBurst('ABCD');

        expect(strict.deliveries).toEqual([]);
    });

    it("prevents the terminator's default action when a burst completes", () => {
        document.body.innerHTML = '<div id="a"></div>';
        router.register(makeTarget({ scope: 'page', element: document.getElementById('a') }).target);

        const events = typeBurst('PRD-1001');

        expect(events.at(-1).defaultPrevented).toBe(true);
    });

    it('never routes slow human typing', () => {
        document.body.innerHTML = '<div id="a"></div>';
        const page = makeTarget({ scope: 'page', element: document.getElementById('a') });
        router.register(page.target);

        typeBurst('PRD-1001', { gapMs: 200 });

        expect(page.deliveries).toEqual([]);
    });
});
