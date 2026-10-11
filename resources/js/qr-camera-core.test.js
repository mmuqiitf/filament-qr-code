import { describe, expect, it, vi } from 'vitest';
import { emitScanFeedback } from './qr-camera-core.js';

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
