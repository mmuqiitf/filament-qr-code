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

    playTones(tones = []) {
        try {
            const context = this.getAudioContext();
            if (!context) return;

            if (context.state === 'suspended') {
                context.resume();
            }

            tones.forEach(({ frequency, durationMs, delayMs = 0 }) => {
                const startAt = context.currentTime + delayMs / 1000;
                const stopAt = startAt + durationMs / 1000;
                const oscillator = context.createOscillator();
                const gain = context.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.setValueAtTime(frequency, startAt);
                gain.gain.setValueAtTime(0.15, startAt);
                gain.gain.exponentialRampToValueAtTime(0.0001, stopAt);

                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start(startAt);
                oscillator.stop(stopAt);
            });
        } catch (error) {
            console.debug('Audio feedback error:', error);
        }
    }

    vibratePattern(pattern = []) {
        try {
            if (typeof navigator !== 'undefined' && navigator.vibrate && pattern.length > 0) {
                navigator.vibrate(pattern);
            }
        } catch (error) {
            console.debug('Haptic feedback error:', error);
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
