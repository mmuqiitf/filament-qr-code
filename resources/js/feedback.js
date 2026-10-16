import { qrFeedback } from './audio-feedback.js';

export const ERROR_TONES = [
    { frequency: 220, durationMs: 90 },
    { frequency: 220, durationMs: 90, delayMs: 140 },
];

export const FLASH_MS = 300;

export const FLASH_CLASSES = {
    success: 'fi-qr-flash-success',
    error: 'fi-qr-flash-danger',
};

const flashTimers = new WeakMap();

export function flashElement(element, className, durationMs = FLASH_MS) {
    clearTimeout(flashTimers.get(element));
    element.classList.remove(...Object.values(FLASH_CLASSES));
    element.classList.add(className);

    flashTimers.set(
        element,
        setTimeout(() => element.classList.remove(className), durationMs),
    );
}

export function playOutcomeFeedback(
    outcome,
    settings,
    { element = null, feedback = qrFeedback, beepFrequency = 880, beepDurationMs = 80 } = {},
) {
    if (!settings) {
        return;
    }

    const { sound = false, vibrate = [], flash = false } = settings;

    if (sound) {
        feedback.playTones(
            outcome === 'success' ? [{ frequency: beepFrequency, durationMs: beepDurationMs }] : ERROR_TONES,
        );
    }

    if (vibrate.length > 0) {
        feedback.vibratePattern(vibrate);
    }

    if (flash && element) {
        flashElement(element, outcome === 'success' ? FLASH_CLASSES.success : FLASH_CLASSES.error);
    }
}
