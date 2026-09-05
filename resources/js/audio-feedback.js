/**
 * Instant zero-latency audio and haptic feedback for QR scanning using Web Audio API.
 */
class QrFeedback {
    constructor() {
        this.audioCtx = null;
    }

    getAudioContext() {
        if (!this.audioCtx && (typeof window !== 'undefined' && (window.AudioContext || window.webkitAudioContext))) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            this.audioCtx = new AudioContextClass();
        }
        return this.audioCtx;
    }

    playSuccessBeep(frequency = 880, durationMs = 80) {
        try {
            const ctx = this.getAudioContext();
            if (!ctx) return;

            if (ctx.state === 'suspended') {
                ctx.resume();
            }

            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(frequency, ctx.currentTime);

            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + (durationMs / 1000));

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start();
            osc.stop(ctx.currentTime + (durationMs / 1000));
        } catch (e) {
            console.debug('Audio feedback error:', e);
        }
    }

    vibrate(durationMs = 100) {
        try {
            if (typeof navigator !== 'undefined' && navigator.vibrate) {
                navigator.vibrate(durationMs);
            }
        } catch (e) {
            console.debug('Haptic feedback error:', e);
        }
    }

    trigger(options = {}) {
        const soundEnabled = options.sound !== false;
        const vibrateEnabled = options.vibrate !== false;

        if (soundEnabled) {
            this.playSuccessBeep(options.frequency || 880, options.duration || 80);
        }

        if (vibrateEnabled) {
            this.vibrate(options.vibrateDuration || 100);
        }
    }
}

export const qrFeedback = new QrFeedback();
