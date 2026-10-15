// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

function mountScanner(options = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"></div>';
    const element = document.querySelector('[data-field-name="sku"]');
    const wire = createWireSpy();
    const scanner = mountComponent(
        qrScannerComponent({ statePath: 'data.sku', sound: false, vibrate: false, hardwareScanner: false, ...options }),
        { element, wire },
    );

    return { scanner, wire };
}

afterEach(() => {
    document.body.innerHTML = '';
});

describe('qrScanner Livewire sync', () => {
    it('writes a scan to Livewire exactly once', () => {
        const { scanner, wire } = mountScanner();

        scanner.handleScanResult('PRD-1001');

        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
    });

    it('does not send a Livewire request for each typed character', () => {
        const { scanner, wire } = mountScanner();

        for (const partialValue of ['P', 'PR', 'PRD', 'PRD-', 'PRD-1']) {
            scanner.value = partialValue;
        }

        expect(wire.calls).toEqual([]);
    });
});
