import { afterEach, describe, expect, it, vi } from 'vitest';
import { createScanDeduper, normalizeScanResult, requestScanCheck } from './scan-pipeline.js';

function clock(start = 0) {
    const state = { now: start };

    return { state, now: () => state.now };
}

describe('createScanDeduper', () => {
    it('same code within 1500 ms is dropped', () => {
        const { state, now } = clock();
        const deduper = createScanDeduper({ now });

        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
        state.now = 1499;
        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(true);
        state.now = 1500;
        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
    });

    it('never drops a different code', () => {
        const { now } = clock();
        const deduper = createScanDeduper({ now });

        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
        expect(deduper.isDuplicate('SKU-2', 1500)).toBe(false);
        expect(deduper.isDuplicate('SKU-1', 1500)).toBe(false);
    });

    it('never drops anything when the cooldown is 0', () => {
        const { now } = clock();
        const deduper = createScanDeduper({ now });

        expect(deduper.isDuplicate('SKU-1', 0)).toBe(false);
        expect(deduper.isDuplicate('SKU-1', 0)).toBe(false);
    });
});

describe('normalizeScanResult', () => {
    const context = { code: 'SKU-1', source: 'camera', scanFailedMessage: 'Check failed' };

    it('maps a server result to the client shape', () => {
        expect(
            normalizeScanResult(
                { passes: true, code: 'SKU-1', source: 'hardware', message: null, record_key: 7 },
                context,
            ),
        ).toEqual({ passes: true, code: 'SKU-1', source: 'hardware', message: null, recordKey: 7 });
    });

    it.each([[null], [undefined], ['oops'], [{}], [{ passes: 'yes' }]])('treats %j as a failed check', (result) => {
        expect(normalizeScanResult(result, context)).toEqual({
            passes: false,
            code: 'SKU-1',
            source: 'camera',
            message: 'Check failed',
            recordKey: null,
        });
    });
});

describe('requestScanCheck', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('calls handleScan once through callSchemaComponentMethod', async () => {
        const calls = [];
        const wire = {
            callSchemaComponentMethod(...args) {
                calls.push(args);

                return Promise.resolve({
                    passes: true,
                    code: 'SKU-1',
                    source: 'camera',
                    message: null,
                    record_key: null,
                });
            },
        };

        const result = await requestScanCheck(wire, {
            componentKey: 'form.sku',
            code: 'SKU-1',
            source: 'camera',
            scanFailedMessage: 'Check failed',
        });

        expect(calls).toEqual([['form.sku', 'handleScan', { code: 'SKU-1', source: 'camera' }]]);
        expect(result.passes).toBe(true);
    });

    it('turns a rejected call into a failed check and logs the error', async () => {
        const errors = vi.spyOn(console, 'error').mockImplementation(() => {});
        const wire = { callSchemaComponentMethod: () => Promise.reject(new Error('offline')) };

        const result = await requestScanCheck(wire, {
            componentKey: 'form.sku',
            code: 'SKU-1',
            source: 'camera',
            scanFailedMessage: 'Check failed',
        });

        expect(result).toEqual({
            passes: false,
            code: 'SKU-1',
            source: 'camera',
            message: 'Check failed',
            recordKey: null,
        });
        expect(errors).toHaveBeenCalledTimes(1);
    });
});
