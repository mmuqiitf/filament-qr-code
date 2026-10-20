// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import { inputRouter } from './input-router.js';
import './index.js';

function typeBurst(target, text) {
    [...text].forEach((key) => target.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true })));
    target.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
}

afterEach(() => {
    inputRouter().destroy();
    document.body.innerHTML = '';
});

describe('public input router', () => {
    it('exposes the shared page router on window.FilamentQrCode', () => {
        expect(window.FilamentQrCode.router).toBe(inputRouter());
        expect(typeof window.FilamentQrCode.createInputRouter).toBe('function');
    });

    it('routes a burst to a custom target registered through it', () => {
        document.body.innerHTML = '<div id="keypad"><input id="pin"></div>';
        const input = document.getElementById('pin');
        const delivered = [];

        const unregister = window.FilamentQrCode.router.register({
            id: 'keypad',
            element: document.getElementById('keypad'),
            kind: 'custom',
            scope: 'focus',
            deliver: (code) => delivered.push(code),
        });

        input.focus();
        typeBurst(input, 'RFID-42');

        expect(delivered).toEqual(['RFID-42']);
        unregister();
        expect(inputRouter().targetCount()).toBe(0);
    });
});
