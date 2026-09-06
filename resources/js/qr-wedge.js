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

/**
 * Alpine component for QrWedgeListener.
 */
export function qrWedgeListenerComponent({
    fields = [],
    burstThresholdMs = 50,
    preventSubmit = true,
    sound = true,
    vibrate = true,
    autoFocusNext = true,
} = {}) {
    return {
        registeredFields: fields,
        wedgeHandler: null,

        init() {
            this.wedgeHandler = createWedgeHandler({
                burstThresholdMs,
                preventFormSubmit: preventSubmit,
                sound,
                vibrate,
                onScan: (scannedValue) => {
                    this.handleGlobalScan(scannedValue);
                },
            });

            window.addEventListener('keydown', (e) => {
                this.wedgeHandler.handleKeyDown(e);
            });
        },

        handleGlobalScan(scannedValue) {
            let targetInput = null;
            let targetFieldName = null;
            const active = document.activeElement;

            if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) {
                targetInput = active;
                targetFieldName = active.getAttribute('name') || active.closest('[data-field-name]')?.getAttribute('data-field-name');
            }

            if (!targetInput && this.registeredFields.length > 0) {
                for (const fieldName of this.registeredFields) {
                    const el = document.querySelector(`[data-field-name="${fieldName}"] input, input[name="${fieldName}"], #${fieldName}`);
                    if (el && !el.value) {
                        targetInput = el;
                        targetFieldName = fieldName;
                        break;
                    }
                }
            }

            if (!targetInput) {
                const inputs = document.querySelectorAll('form input[type="text"]:not([disabled]):not([readonly])');
                for (const input of inputs) {
                    if (!input.value) {
                        targetInput = input;
                        targetFieldName = input.getAttribute('name') || input.closest('[data-field-name]')?.getAttribute('data-field-name');
                        break;
                    }
                }
            }

            if (targetInput) {
                targetInput.focus();
                targetInput.value = scannedValue;
                targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                targetInput.dispatchEvent(new Event('change', { bubbles: true }));

                window.dispatchEvent(new CustomEvent('qr-wedge-scanned', {
                    detail: {
                        value: scannedValue,
                        field: targetFieldName,
                    },
                }));

                if (autoFocusNext) {
                    this.$nextTick(() => {
                        this.advanceToNextEmpty(targetInput);
                    });
                }
            }
        },

        advanceToNextEmpty(currentInput) {
            const allInputs = Array.from(document.querySelectorAll('form input[type="text"]:not([disabled]):not([readonly])'));
            const currentIndex = allInputs.indexOf(currentInput);

            if (currentIndex !== -1 && currentIndex < allInputs.length - 1) {
                const nextInput = allInputs[currentIndex + 1];
                if (nextInput) {
                    nextInput.focus();
                    if (typeof nextInput.select === 'function') {
                        nextInput.select();
                    }
                }
            }
        },
    };
}
