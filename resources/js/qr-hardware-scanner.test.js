import { describe, expect, it, vi, afterEach } from 'vitest';
import {
    createHardwareScannerHandler,
    sanitizeScannedValue,
} from './qr-hardware-scanner.js';
import { qrFeedback } from './audio-feedback.js';

const GLOBAL_KEY = '__filamentQrCodeGlobalHardwareListeners';

function keyEvent(k) {
    return {
        key: k,
        isComposing: false,
        ctrlKey: false,
        altKey: false,
        metaKey: false,
        preventDefault() {},
        stopPropagation() {},
    };
}

function typeBurst(handler, text) {
    for (const ch of text) {
        handler.handleKeyDown(keyEvent(ch));
    }
}

afterEach(() => {
    vi.restoreAllMocks();
    if (typeof window !== 'undefined') {
        delete window[GLOBAL_KEY];
    }
});

describe('sanitizeScannedValue', () => {
    it('strips scanner framing like the PHP mirror', () => {
        expect(sanitizeScannedValue('\x02PROD-9988234-XYZ\r\n\x03')).toBe('PROD-9988234-XYZ');
        expect(sanitizeScannedValue('  ABC-123  ')).toBe('ABC-123');
    });
});

describe('createHardwareScannerHandler', () => {
    it('routes a terminated burst with the sanitized value', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (v) => seen.push(v) });

        typeBurst(handler, 'PRD-1001');

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(true);
        expect(seen).toEqual(['PRD-1001']);
    });

    it('never triggers feedback itself: delivery points own the single beep', async () => {
        const trigger = vi.spyOn(qrFeedback, 'trigger');
        const seen = [];
        const handler = createHardwareScannerHandler({
            scanTimeoutMs: 15,
            onScan: (v) => seen.push(v),
        });

        typeBurst(handler, 'GUN-1');
        handler.handleKeyDown(keyEvent('Enter'));

        typeBurst(handler, 'GUN-2');
        await new Promise((resolve) => setTimeout(resolve, 60));

        expect(seen).toEqual(['GUN-1', 'GUN-2']);
        expect(trigger).not.toHaveBeenCalled();
    });

    it('flushes terminator-less guns via scanTimeoutMs', async () => {
        const seen = [];
        const handler = createHardwareScannerHandler({
            scanTimeoutMs: 15,
            onScan: (v) => seen.push(v),
        });

        typeBurst(handler, 'NO-TERM-9');
        await new Promise((resolve) => setTimeout(resolve, 60));

        expect(seen).toEqual(['NO-TERM-9']);
    });

    it('drops bursts shorter than minBarcodeLength', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({
            minBarcodeLength: 5,
            onScan: (v) => seen.push(v),
        });

        typeBurst(handler, 'AB');
        handler.handleKeyDown(keyEvent('Enter'));

        expect(seen).toEqual([]);
    });

    it('does not mistake slow typing for a burst', async () => {
        const seen = [];
        const handler = createHardwareScannerHandler({
            burstThresholdMs: 10,
            onScan: (v) => seen.push(v),
        });

        handler.handleKeyDown(keyEvent('A'));
        await new Promise((resolve) => setTimeout(resolve, 50));
        handler.handleKeyDown(keyEvent('B'));
        handler.handleKeyDown(keyEvent('Enter'));

        expect(seen).toEqual([]);
    });

    it('stands down while a Station Listener is mounted', () => {
        globalThis.window ??= {};
        window[GLOBAL_KEY] = 1;

        const seen = [];
        const handler = createHardwareScannerHandler({
            suppressWhenGlobalListenerActive: true,
            onScan: (v) => seen.push(v),
        });

        typeBurst(handler, 'PRD-1001');

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(false);
        expect(seen).toEqual([]);
    });

    it('stays active when forced, even with a Station Listener mounted', () => {
        globalThis.window ??= {};
        window[GLOBAL_KEY] = 1;

        const seen = [];
        const handler = createHardwareScannerHandler({
            suppressWhenGlobalListenerActive: false,
            onScan: (v) => seen.push(v),
        });

        typeBurst(handler, 'PRD-1001');

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(true);
        expect(seen).toEqual(['PRD-1001']);
    });
});
