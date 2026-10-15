// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import { mountComponent } from './alpine-test-harness.js';

function mountScanner(markup, options = {}) {
    document.body.innerHTML = markup;
    const element = document.querySelector('[data-scanner]');

    return mountComponent(
        qrScannerComponent({ sound: false, vibrate: false, hardwareScanner: false, ...options }),
        { element },
    );
}

afterEach(() => {
    document.body.innerHTML = '';
});

describe('field lookup (P0.4)', () => {
    it('focuses the exact field, not one whose name merely contains it', () => {
        const scanner = mountScanner(`
            <div data-scanner><input></div>
            <input id="parent" wire:model="data.parent_sku">
            <input id="target" wire:model="data.sku">
        `);

        scanner.advanceFocus('sku');

        expect(document.activeElement?.id).toBe('target');
    });

    it('resolves nested state paths that contain CSS metacharacters', () => {
        const scanner = mountScanner(`
            <div data-scanner><input></div>
            <input id="items.0.sku">
        `);

        scanner.advanceFocus('items.0.sku');

        expect(document.activeElement?.id).toBe('items.0.sku');
    });

    it('reads the state path from any wire:model modifier', () => {
        for (const attribute of ['wire:model.blur', 'wire:model.live.debounce.500ms']) {
            const scanner = mountScanner(`<div data-scanner><input ${attribute}="data.sku"></div>`);

            expect(scanner.getStatePath()).toBe('data.sku');
        }
    });

    it('lets the station listener find nested registered fields', () => {
        document.body.innerHTML = '<form><input id="items.0.sku" type="text"></form>';
        const element = document.createElement('div');
        const listener = mountComponent(qrHardwareScannerListenerComponent({ fields: ['items.0.sku'] }), { element });

        expect(listener.findFieldElement('items.0.sku')?.id).toBe('items.0.sku');
    });
});
