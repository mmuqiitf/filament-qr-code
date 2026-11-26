import { describe, expect, it, vi } from 'vitest';
import { computeQrboxForElement, emitScanFeedback, syncReticleToQrbox } from './qr-camera-core.js';

describe('emitScanFeedback', () => {
    it('delivers one trigger with mapped option names', () => {
        const feedback = { trigger: vi.fn() };

        emitScanFeedback(feedback, {
            sound: true,
            vibrate: true,
            beepFrequency: 660,
            beepDurationMs: 120,
            vibrateDurationMs: 200,
        });

        expect(feedback.trigger).toHaveBeenCalledTimes(1);
        expect(feedback.trigger).toHaveBeenCalledWith({
            sound: true,
            vibrate: true,
            frequency: 660,
            duration: 120,
            vibrateDuration: 200,
        });
    });

    it('passes sound/vibrate opt-outs through to the trigger', () => {
        const feedback = { trigger: vi.fn() };

        emitScanFeedback(feedback, { sound: false, vibrate: false });

        expect(feedback.trigger).toHaveBeenCalledTimes(1);
        expect(feedback.trigger).toHaveBeenCalledWith(
            expect.objectContaining({ sound: false, vibrate: false }),
        );
    });
});

describe('computeQrboxForElement', () => {
    const viewfinder = { clientWidth: 480, clientHeight: 360 };

    it('uses a wide band for 1D-only fields', () => {
        const box = computeQrboxForElement(viewfinder, 250, ['CODE_128', 'EAN_13']);

        expect(box.width).toBe(250);
        expect(box.height).toBeLessThan(box.width);
    });

    it('uses a square box when QR is allowed alongside 1D formats', () => {
        expect(computeQrboxForElement(viewfinder, 250, ['QR_CODE', 'CODE_128'])).toEqual({ width: 250, height: 250 });
    });

    it.each(['DATA_MATRIX', 'AZTEC'])('uses a square box when %s is allowed alongside 1D formats', (format) => {
        expect(computeQrboxForElement(viewfinder, 250, [format, 'EAN_13'])).toEqual({ width: 250, height: 250 });
    });

    it('keeps the square box inside short viewfinders', () => {
        const box = computeQrboxForElement({ clientWidth: 480, clientHeight: 200 }, 250, ['QR_CODE', 'CODE_128']);

        expect(box).toEqual({ width: 170, height: 170 });
    });

    it('uses a square box when no formats are restricted', () => {
        expect(computeQrboxForElement(viewfinder, 250, [])).toEqual({ width: 250, height: 250 });
    });
});

describe('scanRegion in computeQrboxForElement & syncReticleToQrbox', () => {
    const viewfinder = { clientWidth: 480, clientHeight: 360 };

    it('computes ratio-based scanRegion dimensions inside viewfinder', () => {
        expect(computeQrboxForElement(viewfinder, 250, ['QR_CODE'], { width: 0.85, height: 0.35 })).toEqual({
            width: 408,
            height: 126,
        });
    });

    it('clamps pixel-based scanRegion to viewfinder bounds and minimum size', () => {
        expect(computeQrboxForElement({ clientWidth: 300, clientHeight: 200 }, 250, [], { width: 420, height: 40 })).toEqual({
            width: 300,
            height: 80,
        });
    });

    it('syncs non-square scanRegion dimensions onto the reticle box', () => {
        const box = { style: {} };
        const scope = { querySelector: (selector) => (selector === '.filament-qr-reticle-box' ? box : null) };
        const element = { closest: () => scope };

        syncReticleToQrbox(element, { width: 384, height: 198 });

        expect(box.style.width).toBe('384px');
        expect(box.style.height).toBe('198px');
    });
});