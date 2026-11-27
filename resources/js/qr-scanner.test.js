// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { clearDocument, mountSkuField } from './alpine-test-harness.js';

function mountScanner(options = {}) {
    return mountSkuField(qrScannerComponent({ statePath: 'data.sku', sound: false, vibrate: false, hardwareScanner: false, ...options }));
}

afterEach(clearDocument);

describe('qrScanner Livewire sync', () => {
    it('writes a scan to Livewire exactly once', () => {
        const { scanner, wire } = mountScanner();

        scanner.handleScanResult('PRD-1001');

        expect(wire.calls).toEqual([['data.sku', 'PRD-1001']]);
    });
});
