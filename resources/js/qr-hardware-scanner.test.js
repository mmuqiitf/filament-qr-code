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

    it('stays active with a Station Listener mounted: the router picks one owner', () => {
        globalThis.window ??= {};
        window[GLOBAL_KEY] = 1;

        const seen = [];
        const handler = createHardwareScannerHandler({
            onScan: (v) => seen.push(v),
        });

        typeBurst(handler, 'PRD-1001');

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(true);
        expect(seen).toEqual(['PRD-1001']);
    });
});

describe('burst hardening (P0.2)', () => {
    function repeatEvent(key) {
        return { ...keyEvent(key), repeat: true };
    }

    it('never treats a held-down key (auto-repeat) as a scan', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        handler.handleKeyDown(keyEvent('a'));
        for (let index = 0; index < 6; index++) {
            handler.handleKeyDown(repeatEvent('a'));
        }

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(false);
        expect(seen).toEqual([]);
    });

    it('requires minFastKeys consecutive fast gaps before a terminator counts', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ minFastKeys: 3, onScan: (value) => seen.push(value) });

        typeBurst(handler, 'AB');
        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(false);

        typeBurst(handler, 'ABCDE');
        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(true);
        expect(seen).toEqual(['ABCDE']);
    });

    it('needs three fast gaps by default', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        typeBurst(handler, 'AB');

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(false);
        expect(seen).toEqual([]);
    });

    it('restores the 0.x behaviour with minFastKeys: 1', () => {
        const seen = [];
        const handler = createHardwareScannerHandler({ minFastKeys: 1, onScan: (value) => seen.push(value) });

        typeBurst(handler, 'AB');

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(true);
        expect(seen).toEqual(['AB']);
    });
});

describe('burst timing: scanner speed on average, not a few fast pairs', () => {
    function typeTimed(handler, steps) {
        // steps: [key, msBeforeKey][]
        for (const [key, wait] of steps) {
            vi.advanceTimersByTime(wait);
            handler.handleKeyDown(keyEvent(key));
        }
    }

    it('does not treat fast human typing with a few quick pairs as a scan', () => {
        vi.useFakeTimers();
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        // "mirror scratch": rollover pairs at 30 ms among 90-140 ms gaps.
        const gaps = [0, 30, 120, 30, 110, 30, 140, 90, 30, 100, 30, 120, 90, 30];
        typeTimed(handler, [...'mirror scratch'].map((key, index) => [key, gaps[index]]));
        vi.advanceTimersByTime(300);
        handler.handleKeyDown(keyEvent('Enter'));

        expect(seen).toEqual([]);
        vi.useRealTimers();
    });

    it('accepts a short shifted code with one slow gap, the way a Bluetooth gun sends DONE', () => {
        vi.useFakeTimers();
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        typeTimed(handler, [
            ['Shift', 0], ['D', 4],
            ['Shift', 20], ['O', 4],
            ['Shift', 70], ['N', 4],
            ['Shift', 20], ['E', 4],
        ]);
        vi.advanceTimersByTime(20);

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(true);
        expect(seen).toEqual(['DONE']);
        vi.useRealTimers();
    });

    it('accepts a short code whose gaps hover around the threshold', () => {
        vi.useFakeTimers();
        const seen = [];
        const handler = createHardwareScannerHandler({ onScan: (value) => seen.push(value) });

        typeTimed(handler, [['D', 0], ['O', 40], ['N', 65], ['E', 35]]);
        vi.advanceTimersByTime(40);

        expect(handler.handleKeyDown(keyEvent('Enter'))).toBe(true);
        expect(seen).toEqual(['DONE']);
        vi.useRealTimers();
    });
});
