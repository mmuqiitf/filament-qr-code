import { qrFeedback } from './audio-feedback.js';
import { emitScanFeedback } from './qr-camera-core.js';
import { readWireModelPath, findFieldInput } from './field-locator.js';

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
 * Seam discipline: this Module only detects, sanitizes, and routes. It never
 * beeps. Each delivery point (field, sequence, collector, Station Listener)
 * owns exactly one feedback call, so one gun burst always yields one beep.
 *
 * Guards: IME compositions and modifier-held keys are never buffered, a
 * burst requires consecutive fast gaps (a single fast pair amid slow typing
 * is not enough), and terminator-less guns flush via scanTimeoutMs.
 */
export function createHardwareScannerHandler({
    burstThresholdMs = 50,
    minBarcodeLength = 2,
    minFastKeys = 1,
    preventFormSubmit = true,
    terminators = ['Enter', 'Tab'],
    scanTimeoutMs = 150,
    suppressWhenGlobalListenerActive = false,
    isGlobalListener = false,
    onScan = null,
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

        // No feedback here: the onScan delivery point owns the single beep.
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
            if (isBursting && fastStreak >= Math.max(2, minFastKeys) && buffer.length >= minBarcodeLength) {
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

            // Auto-repeat comes from a held-down key; scanners never emit it.
            if (event.repeat) {
                return false;
            }

            const now = Date.now();
            const timeDiff = now - lastKeyTime;
            lastKeyTime = now;

            const isTerminator = terminators.includes(event.key);

            if (isTerminator) {
                if (isBursting && fastStreak >= minFastKeys && buffer.length >= minBarcodeLength) {
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

function isTextEntry(element) {
    return Boolean(element) && (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA');
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
    minFastKeys = 1,
    scanTimeoutMs = 150,
    autoFocusNext = true,
    fallbackToAnyInput = true,
} = {}) {
    // Filament text inputs plus numeric / textarea fallbacks for cashier forms.
    const SCANNABLE_SELECTOR = 'input[type="text"], input[type="search"], input[type="number"], textarea';

    return {
        registeredFields: fields,
        hardwareScannerHandler: null,
        boundKeyHandler: null,
        // Focused input and its value when a keystroke sequence started, so
        // a burst routed elsewhere can undo the keys it typed into it.
        burstOrigin: null,
        lastKeyAt: 0,

        init() {
            trackGlobalListener(1);

            this.hardwareScannerHandler = createHardwareScannerHandler({
                burstThresholdMs,
                minBarcodeLength,
                minFastKeys,
                preventFormSubmit: preventSubmit,
                terminators,
                scanTimeoutMs,
                isGlobalListener: true,
                onScan: (scannedValue) => {
                    this.handleGlobalScan(scannedValue);
                },
            });

            this.boundKeyHandler = (e) => {
                this.rememberBurstOrigin();
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
            return findFieldInput(fieldName);
        },

        rememberBurstOrigin() {
            const now = Date.now();
            const startsSequence = now - this.lastKeyAt > burstThresholdMs;
            this.lastKeyAt = now;

            if (!startsSequence) {
                return;
            }

            const active = document.activeElement;
            this.burstOrigin = isTextEntry(active) ? { element: active, value: active.value } : null;
        },

        isRegisteredField(element) {
            const name = element.getAttribute('name') || element.closest('[data-field-name]')?.getAttribute('data-field-name');

            return this.registeredFields.some((fieldName) => findFieldInput(fieldName) === element || fieldName === name);
        },

        restoreBurstOrigin(targetInput) {
            const origin = this.burstOrigin;
            this.burstOrigin = null;

            if (!origin || origin.element === targetInput || origin.element.value === origin.value) {
                return;
            }

            origin.element.value = origin.value;
            origin.element.dispatchEvent(new Event('input', { bubbles: true }));
            origin.element.dispatchEvent(new Event('change', { bubbles: true }));
        },

        handleGlobalScan(scannedValue) {
            let targetInput = null;
            let targetFieldName = null;
            const active = document.activeElement;

            // With registered fields, a focused input only receives the scan
            // when it is one of them (a focused search/member box is not).
            if (isTextEntry(active) && (this.registeredFields.length === 0 || this.isRegisteredField(active))) {
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

            if (!targetInput && fallbackToAnyInput) {
                const inputs = document.querySelectorAll(`form ${SCANNABLE_SELECTOR}:not([disabled]):not([readonly])`);
                for (const input of inputs) {
                    if (!input.value) {
                        targetInput = input;
                        targetFieldName = input.getAttribute('name') || input.closest('[data-field-name]')?.getAttribute('data-field-name');
                        break;
                    }
                }
            }

            if (!targetInput) {
                window.dispatchEvent(new CustomEvent('qr-scan-unrouted', {
                    detail: { value: scannedValue },
                }));

                return;
            }

            if (targetInput) {
                this.restoreBurstOrigin(targetInput);
                targetInput.focus();
                targetInput.value = scannedValue;
                targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                targetInput.dispatchEvent(new Event('change', { bubbles: true }));

                // The single beep for this delivery: the interceptor that
                // routed the burst never beeps, so one burst yields one beep.
                emitScanFeedback(qrFeedback, {
                    sound,
                    vibrate,
                    beepFrequency,
                    beepDurationMs,
                    vibrateDurationMs,
                });

                // Keep Livewire / entangled Alpine state in sync when the DOM
                // event alone is not enough (e.g. x-model bound scanner fields).
                try {
                    const livewirePath = readWireModelPath(targetInput);
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
