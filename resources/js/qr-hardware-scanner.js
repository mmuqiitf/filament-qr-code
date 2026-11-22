import { qrFeedback } from './audio-feedback.js';
import { emitScanFeedback } from './qr-camera-core.js';
import { readWireModelPath, findFieldInput } from './field-locator.js';
import { inputRouter } from './input-router.js';

const GLOBAL_LISTENER_KEY = '__filamentQrCodeGlobalHardwareListeners';

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

/** GS1 group separator (ASCII 29), which scanners send for FNC1 between fields. */
export const GROUP_SEPARATOR = '\x1D';

function isGroupSeparatorChord(event) {
    return event.ctrlKey && !event.altKey && !event.metaKey && !event.isComposing
        && (event.key === ']' || event.code === 'BracketRight' || event.key === GROUP_SEPARATOR);
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
 * burst needs at least minFastKeys gaps that average burstThresholdMs or
 * less (a few quick pairs amid human typing are not enough), and
 * terminator-less guns flush via scanTimeoutMs.
 */
export function createHardwareScannerHandler({
    burstThresholdMs = 50,
    minBarcodeLength = 2,
    minFastKeys = 3,
    preventFormSubmit = true,
    terminators = ['Enter', 'Tab'],
    scanTimeoutMs = 150,
    onScan = null,
} = {}) {
    let buffer = '';
    let lastKeyTime = 0;
    // Gaps (ms) before each buffered character after the first.
    let gaps = [];
    let flushTimer = null;

    const clearFlushTimer = () => {
        if (flushTimer) {
            clearTimeout(flushTimer);
            flushTimer = null;
        }
    };

    const clearBuffer = () => {
        buffer = '';
        gaps = [];
        clearFlushTimer();
    };

    // A scan: at least minFastKeys gaps, at scanner speed on average. A few
    // quick pairs amid human typing average well above the threshold, and a
    // gun with one slow gap (Bluetooth jitter) still averages below it.
    const looksLikeScan = () => {
        if (gaps.length < minFastKeys || buffer.length < minBarcodeLength) {
            return false;
        }

        const mean = gaps.reduce((total, gap) => total + gap, 0) / gaps.length;

        return mean <= burstThresholdMs;
    };

    const emit = (rawValue) => {
        const scannedValue = sanitizeScannedValue(rawValue);
        clearBuffer();

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
            flushTimer = null;

            // Terminator-less gun: a completed burst with no suffix key.
            if (looksLikeScan()) {
                emit(buffer);
            } else {
                buffer = '';
                gaps = [];
            }
        }, scanTimeoutMs);
    };

    return {
        handleKeyDown(event) {
            // Keyboard-wedge guns type the GS1 group separator (FNC1) as Ctrl+].
            // Only inside a burst, so the shortcut itself is never buffered.
            if (isGroupSeparatorChord(event) && buffer.length > 0 && !event.repeat) {
                lastKeyTime = Date.now();
                buffer += GROUP_SEPARATOR;
                scheduleFlush();

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

            // Shift and other non-printing keys still mark the time, so a
            // gun's Shift + letter counts as one quick gap.
            const now = Date.now();
            const timeDiff = now - lastKeyTime;
            lastKeyTime = now;

            if (terminators.includes(event.key)) {
                if (looksLikeScan()) {
                    if (preventFormSubmit) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    return emit(buffer);
                }

                clearBuffer();

                return false;
            }

            // Record standard printable characters only.
            if (event.key.length === 1) {
                if (buffer.length > 0 && timeDiff > burstThresholdMs * 3) {
                    // A long pause: whatever came before was not this scan.
                    buffer = '';
                    gaps = [];
                } else if (buffer.length > 0) {
                    gaps.push(timeDiff);
                }

                buffer += event.key;
                scheduleFlush();
            }

            return false;
        },

        reset() {
            clearBuffer();
            lastKeyTime = 0;
        },

        getBuffer() {
            return buffer;
        },
    };
}

function isTextEntry(element) {
    return Boolean(element) && (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA');
}

function isWritable(element) {
    return Boolean(element) && !element.disabled && !element.readOnly;
}

function fieldNameOf(element) {
    return element.getAttribute('name') || element.closest('[data-field-name]')?.getAttribute('data-field-name');
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
    minFastKeys = 3,
    scanTimeoutMs = 150,
    autoFocusNext = true,
    fallbackToAnyInput = true,
    gs1 = false,
    componentKey = null,
    scanFailedMessage = null,
} = {}) {
    // Filament text inputs plus numeric / textarea fallbacks for cashier forms.
    const SCANNABLE_SELECTOR = 'input[type="text"], input[type="search"], input[type="number"], textarea';

    // GS1 mode: the server parses each burst and fills the mapped fields.
    const checksGs1 = Boolean(gs1 && componentKey);

    return {
        registeredFields: fields,
        unregisterTarget: null,
        rejectionMessage: '',

        init() {
            trackGlobalListener(1);

            this.unregisterTarget = inputRouter().register({
                id: `listener-${this.$id('qr-listener')}-${Math.random().toString(36).slice(2)}`,
                element: this.$root ?? this.$el,
                name: null,
                kind: 'listener',
                scope: 'page',
                fallback: fallbackToAnyInput,
                detection: { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs, preventSubmit },
                candidates: () => (checksGs1 ? [this.$root ?? this.$el] : this.registeredFields
                    .map((fieldName) => this.findFieldElement(fieldName))
                    .filter((element) => isWritable(element) && !element.value)),
                ownsFocus: (element) => isTextEntry(element)
                    && (this.registeredFields.length === 0 || this.isRegisteredField(element)),
                deliver: (scannedValue, { element }) => {
                    this.handleGlobalScan(scannedValue, isTextEntry(element) ? element : null);
                },
            });
        },

        destroy() {
            trackGlobalListener(-1);
            this.unregisterTarget?.();
            this.unregisterTarget = null;
        },

        findFieldElement(fieldName) {
            return findFieldInput(fieldName);
        },

        isRegisteredField(element) {
            const name = element.getAttribute('name') || element.closest('[data-field-name]')?.getAttribute('data-field-name');

            return this.registeredFields.some((fieldName) => findFieldInput(fieldName) === element || fieldName === name);
        },

        async handleGs1Scan(scannedValue) {
            let result = null;

            try {
                result = await this.$wire.callSchemaComponentMethod(componentKey, 'handleGs1Scan', { code: scannedValue });
            } catch (error) {
                console.error('GS1 scan check failed:', error);
            }

            if (!result || result.passes !== true) {
                // Never beep or announce a refused label as scanned.
                const message = result?.message ?? scanFailedMessage;
                this.rejectionMessage = message ?? '';
                window.dispatchEvent(new CustomEvent('qr-scan-rejected', {
                    detail: { value: scannedValue, field: null, source: 'hardware', message },
                }));

                return result;
            }

            this.rejectionMessage = '';

            emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });

            window.dispatchEvent(new CustomEvent('qr-hardware-scanned', {
                detail: { value: result.code ?? scannedValue, field: null, gs1: true },
            }));

            return result;
        },

        handleGlobalScan(scannedValue, preferredInput = null) {
            if (checksGs1 && this.$wire) {
                return this.handleGs1Scan(scannedValue);
            }

            let targetInput = null;
            let targetFieldName = null;
            const active = document.activeElement;

            if (preferredInput) {
                targetInput = preferredInput;
                targetFieldName = fieldNameOf(preferredInput);
            }

            // With registered fields, a focused input only receives the scan
            // when it is one of them (a focused search/member box is not).
            if (!targetInput && isTextEntry(active) && (this.registeredFields.length === 0 || this.isRegisteredField(active))) {
                targetInput = active;
                targetFieldName = active.getAttribute('name') || active.closest('[data-field-name]')?.getAttribute('data-field-name');
            }

            if (!targetInput && this.registeredFields.length > 0) {
                for (const fieldName of this.registeredFields) {
                    const el = this.findFieldElement(fieldName);
                    if (isWritable(el) && !el.value) {
                        targetInput = el;
                        targetFieldName = fieldName;
                        break;
                    }
                }
            }

            // Only the form that contains this listener. A search box in
            // another form stays empty; with no enclosing form, nothing matches.
            if (!targetInput && fallbackToAnyInput) {
                const form = (this.$root ?? this.$el)?.closest?.('form') ?? null;
                const inputs = form
                    ? form.querySelectorAll(`${SCANNABLE_SELECTOR}:not([disabled]):not([readonly])`)
                    : [];

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
                targetInput.focus();
                const deliverEvent = new CustomEvent('qr-scan-deliver', {
                    bubbles: true,
                    cancelable: true,
                    detail: { value: scannedValue },
                });

                if (!targetInput.dispatchEvent(deliverEvent)) {
                    window.dispatchEvent(new CustomEvent('qr-hardware-scanned', {
                        detail: { value: scannedValue, field: targetFieldName },
                    }));

                    return;
                }

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
