import { qrFeedback } from './audio-feedback.js';

/**
 * Hardware Keyboard Wedge Scanner Interceptor.
 * Detects rapid burst keystrokes typical of USB/Bluetooth barcode guns (<50ms per key),
 * suppresses default submit action on terminating Enter/Tab, and coordinates field updates.
 */
export function createWedgeHandler({
    burstThresholdMs = 50,
    minBarcodeLength = 2,
    preventFormSubmit = true,
    terminators = ['Enter', 'Tab'],
    onScan = null,
    sound = true,
    vibrate = true,
} = {}) {
    let buffer = '';
    let lastKeyTime = 0;
    let isBursting = false;

    return {
        handleKeyDown(event) {
            const now = Date.now();
            const timeDiff = now - lastKeyTime;
            lastKeyTime = now;

            const isTerminator = terminators.includes(event.key);

            // If time between keystrokes is very fast, we are in a scanner burst
            if (timeDiff <= burstThresholdMs) {
                isBursting = true;
            } else if (timeDiff > burstThresholdMs * 3) {
                // Too slow, reset burst buffer
                buffer = '';
                isBursting = false;
            }

            if (isTerminator) {
                if (isBursting && buffer.length >= minBarcodeLength) {
                    if (preventFormSubmit) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    const scannedValue = buffer.trim();
                    buffer = '';
                    isBursting = false;

                    qrFeedback.trigger({ sound, vibrate });

                    if (typeof onScan === 'function') {
                        onScan(scannedValue);
                    }

                    return true;
                }

                buffer = '';
                isBursting = false;
                return false;
            }

            // Record standard printable characters
            if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
                buffer += event.key;
            }

            return false;
        },

        reset() {
            buffer = '';
            isBursting = false;
            lastKeyTime = 0;
        },

        getBuffer() {
            return buffer;
        },
    };
}
