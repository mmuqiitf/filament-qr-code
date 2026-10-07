import { qrFeedback } from './audio-feedback.js';

const GLOBAL_LISTENER_KEY = '__filamentQrCodeGlobalHardwareListeners';

function globalListenerCount() {
    if (typeof window === 'undefined') {
        return 0;
    }

    return window[GLOBAL_LISTENER_KEY] || 0;
}

function trackGlobalListener(delta) {
    if (typeof window === 'undefined') {
        return 0;
    }

    window[GLOBAL_LISTENER_KEY] = Math.max(0, (window[GLOBAL_LISTENER_KEY] || 0) + delta);

    return window[GLOBAL_LISTENER_KEY];
}

/**
 * Strip handheld-scanner framing (STX/ETX, CR/LF) and surrounding
 * whitespace. Mirrors PHP HasHardwareScanner::sanitizeScannedValue().
 */
export function sanitizeScannedValue(value) {
    // eslint-disable-next-line no-control-regex
    return String(value ?? '').replace(/^[\u0000-\u0020\u007F]+|[\u0000-\u0020\u007F]+$/g, '');
}

export function isGlobalHardwareListenerActive() {
    return globalListenerCount() > 0;
}

/**
 * Hardware keyboard scanner interceptor.
 * Detects rapid burst keystrokes typical of USB/Bluetooth barcode guns,
 * suppresses default submit action on terminating Enter/Tab, and coordinates field updates.
 *
 * Guards: IME compositions and modifier-held keys are never buffered, a
 * burst requires consecutive fast gaps (a single fast pair amid slow typing
 * is not enough), and terminator-less guns flush via scanTimeoutMs.
 */
export function createHardwareScannerHandler({
    burstThresholdMs = 50,
    minBarcodeLength = 2,
    preventFormSubmit = true,
    terminators = ['Enter', 'Tab'],
    scanTimeoutMs = 150,
    suppressWhenGlobalListenerActive = false,
    isGlobalListener = false,
    onScan = null,
    sound = true,
    vibrate = true,
    beepFrequency = 880,
    beepDurationMs = 80,
    vibrateDurationMs = 100,
} = {}) {
    let buffer = '';
    let lastKeyTime = 0;
    let fastStreak = 0;
    let isBursting = false;
    let flushTimer = null;

    const clearFlushTimer = () => {
        if (flushTimer) {
            clearTimeout(flushTimer);
            flushTimer = null;
        }
    };

    const emit = (rawValue) => {
        const scannedValue = sanitizeScannedValue(rawValue);
        buffer = '';
        fastStreak = 0;
        isBursting = false;
        clearFlushTimer();

        if (scannedValue.length < minBarcodeLength) {
            return false;
        }

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
    };

    const scheduleFlush = () => {
        if (!scanTimeoutMs || scanTimeoutMs <= 0) {
            return;
        }

        clearFlushTimer();
        flushTimer = setTimeout(() => {
            // Terminator-less gun: a completed burst with no suffix key.
            // Only flush when the burst actually looked like a scan.
            if (isBursting && fastStreak >= 2 && buffer.length >= minBarcodeLength) {
                emit(buffer);
            } else if (!isBursting) {
                buffer = '';
                fastStreak = 0;
            }
        }, scanTimeoutMs);
    };

    return {
        handleKeyDown(event) {
            // Field-scoped handlers stand down while a page-global Station
            // Listener is mounted, so one burst is never handled twice.
            if (suppressWhenGlobalListenerActive && !isGlobalListener && isGlobalHardwareListenerActive()) {
                return false;
            }

            // IME compositions / modifier-held keys are human typing, not scans.
            if (event.isComposing || event.ctrlKey || event.altKey || event.metaKey) {
                return false;
            }

            const now = Date.now();
            const timeDiff = now - lastKeyTime;
            lastKeyTime = now;

            const isTerminator = terminators.includes(event.key);

            if (isTerminator) {
                if (isBursting && fastStreak >= 1 && buffer.length >= minBarcodeLength) {
                    if (preventFormSubmit) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    return emit(buffer);
                }

                buffer = '';
                fastStreak = 0;
                isBursting = false;
                clearFlushTimer();

                return false;
            }

            // Record standard printable characters only.
            if (event.key.length === 1) {
                if (timeDiff <= burstThresholdMs && buffer.length > 0) {
                    fastStreak += 1;
                    isBursting = fastStreak >= 1;
                } else if (timeDiff > burstThresholdMs * 3) {
                    buffer = '';
                    fastStreak = 0;
                    isBursting = false;
                }
                // Ambiguous middle zone (1x-3x threshold): keep buffering
                // without growing the streak, so slow typists never qualify.

                buffer += event.key;
                scheduleFlush();
            }

            return false;
        },

        reset() {
            buffer = '';
            fastStreak = 0;
            isBursting = false;
            lastKeyTime = 0;
            clearFlushTimer();
        },

        getBuffer() {
            return buffer;
        },
    };
}

/**
 * Alpine component for QrHardwareScannerListener.
 */
export function qrHardwareScannerListenerComponent({
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
    scanTimeoutMs = 150,
    autoFocusNext = true,
} = {}) {
    // Filament text inputs plus numeric / textarea fallbacks for cashier forms.
    const SCANNABLE_SELECTOR = 'input[type="text"], input[type="search"], input[type="number"], textarea';

    return {
        registeredFields: fields,
        hardwareScannerHandler: null,
        boundKeyHandler: null,

        init() {
            trackGlobalListener(1);

            this.hardwareScannerHandler = createHardwareScannerHandler({
                burstThresholdMs,
                minBarcodeLength,
                preventFormSubmit: preventSubmit,
                terminators,
                scanTimeoutMs,
                isGlobalListener: true,
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
                this.hardwareScannerHandler.handleKeyDown(e);
            };
            window.addEventListener('keydown', this.boundKeyHandler);
        },

        destroy() {
            trackGlobalListener(-1);

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

                window.dispatchEvent(new CustomEvent('qr-hardware-scanned', {
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
