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
    beepFrequency = 880,
    beepDurationMs = 80,
    vibrateDurationMs = 100,
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

                    qrFeedback.trigger({
                        sound,
                        vibrate,
                        frequency: beepFrequency,
                        duration: beepDurationMs,
                        vibrateDuration: vibrateDurationMs,
                    });

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
    beepFrequency = 880,
    beepDurationMs = 80,
    vibrateDurationMs = 100,
    terminators = ['Enter', 'Tab'],
    minBarcodeLength = 2,
    autoFocusNext = true,
} = {}) {
    // Filament text inputs plus numeric / textarea fallbacks for cashier forms.
    const SCANNABLE_SELECTOR = 'input[type="text"], input[type="search"], input[type="number"], textarea';

    return {
        registeredFields: fields,
        wedgeHandler: null,
        boundKeyHandler: null,

        init() {
            this.wedgeHandler = createWedgeHandler({
                burstThresholdMs,
                minBarcodeLength,
                preventFormSubmit: preventSubmit,
                terminators,
                sound,
                vibrate,
                beepFrequency,
                beepDurationMs,
                vibrateDurationMs,
                onScan: (scannedValue) => {
                    this.handleGlobalScan(scannedValue);
                },
            });

            this.boundKeyHandler = (e) => {
                this.wedgeHandler.handleKeyDown(e);
            };
            window.addEventListener('keydown', this.boundKeyHandler);
        },

        destroy() {
            if (this.boundKeyHandler) {
                window.removeEventListener('keydown', this.boundKeyHandler);
                this.boundKeyHandler = null;
            }
        },

        findFieldElement(fieldName) {
            const selectors = [
                `[data-field-name="${fieldName}"] input`,
                `[data-field-name="${fieldName}"] textarea`,
                `input[name="${fieldName}"]`,
                `textarea[name="${fieldName}"]`,
                `#${fieldName}`,
            ];

            for (const selector of selectors) {
                try {
                    const el = document.querySelector(selector);
                    if (el) {
                        return el;
                    }
                } catch {
                    // Ignore invalid selector characters in dynamic field names.
                }
            }

            return null;
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
                    const el = this.findFieldElement(fieldName);
                    if (el && !el.value && !el.disabled && !el.readOnly) {
                        targetInput = el;
                        targetFieldName = fieldName;
                        break;
                    }
                }
            }

            if (!targetInput) {
                const inputs = document.querySelectorAll(`form ${SCANNABLE_SELECTOR}:not([disabled]):not([readonly])`);
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

                // Keep Livewire / entangled Alpine state in sync when the DOM
                // event alone is not enough (e.g. x-model bound scanner fields).
                try {
                    const livewirePath = targetInput.getAttribute('wire:model')
                        || targetInput.getAttribute('wire:model.defer')
                        || targetInput.getAttribute('wire:model.live');
                    if (livewirePath && this.$wire) {
                        this.$wire.set(livewirePath, scannedValue);
                    }
                } catch {
                    // DOM events above are the primary sync channel.
                }

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
            const allInputs = Array.from(document.querySelectorAll(
                `form ${SCANNABLE_SELECTOR}:not([disabled]):not([readonly])`
            ));
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
