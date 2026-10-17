import { qrFeedback } from './audio-feedback.js';
import { sanitizeScannedValue } from './qr-hardware-scanner.js';
import { inputRouter } from './input-router.js';
import {
    emitScanFeedback,
    ensureScannerInstance,
    loadCameraDevices,
    persistCameraId,
    resolveDecoderModule,
    resolveQrboxFor,
    scannerFormatsConfigFor,
    stopCameraFeed,
    storageKeyFor,
} from './qr-camera-core.js';

/**
 * Alpine component for QrScanSequence container.
 */
export default function qrScanSequenceComponent({
    fields = [],
    sound = true,
    vibrate = true,
    beepFrequency = 880,
    beepDurationMs = 80,
    vibrateDurationMs = 100,
    hardwareScanner = true,
    burstThresholdMs = 50,
    terminators = ['Enter', 'Tab'],
    minBarcodeLength = 2,
    minFastKeys = 1,
    scanTimeoutMs = 150,
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    statePrefix = 'data',
    componentStatePath = null,
    editable = true,
    serverCheck = false,
    steps = [],
    componentKey = null,
    scanFailedMessage = 'Scan failed.',
    cameraStorageKey = null,
    insecureMessage = 'Camera needs a secure context: serve this page over https or open it on localhost, then allow camera access.',
} = {}) {
    const storageKey = cameraStorageKey || storageKeyFor('sequence');

    return {
        fields: fields, // array of { key: string, label: string }
        currentFieldIndex: 0,
        results: {},
        editable: editable,
        isScanning: false,
        isLoading: false,
        hasError: false,
        errorMessage: '',
        devices: [],
        selectedDeviceId: null,
        html5Qrcode: null,
        elementId: '',
        unregisterTarget: null,
        queuedScan: null,
        isChecking: false,
        stepError: null,

        init() {
            this.elementId = `qr-sequence-${this.$id('qr-seq')}`;

            if (hardwareScanner) {
                this.unregisterTarget = inputRouter().register({
                    id: `sequence-${this.elementId}-${Math.random().toString(36).slice(2)}`,
                    element: this.$root ?? this.$el,
                    name: null,
                    kind: 'sequence',
                    scope: 'page',
                    detection: { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs },
                    // While a server check runs, refuse bursts so the router can hold one for us.
                    accept: () => !this.isChecking,
                    hold: (scannedValue) => this.holdScan(scannedValue),
                    deliver: (scannedValue) => {
                        this.focusStepFromInput();
                        this.processScan(scannedValue, 'hardware');
                    },
                });
            }

            this.loadCameras();
        },

        destroy() {
            this.unregisterTarget?.();
            this.unregisterTarget = null;
            this.stopScanner();
        },

        getCurrentField() {
            return this.fields[this.currentFieldIndex] || null;
        },

        setCurrentField(index) {
            if (index >= 0 && index < this.fields.length) {
                this.currentFieldIndex = index;
            }
        },

        async loadCameras() {
            let decoder;
            try {
                decoder = await resolveDecoderModule();
            } catch {
                this.hasError = true;
                this.errorMessage = 'Failed to load camera decoder.';
                return;
            }

            await loadCameraDevices(this, {
                Html5QrcodeClass: decoder.Html5Qrcode,
                preferRearCamera,
                storageKey,
                deniedMessage: 'Camera access denied or unavailable.',
                insecureMessage,
                fixedMessage: true,
            });
        },

        scannerFormatsConfig() {
            return scannerFormatsConfigFor(formats);
        },

        currentQrbox() {
            return resolveQrboxFor(this.elementId, qrbox, formats);
        },

        statePathFor(fieldKey) {
            const prefix = (statePrefix || '').trim().replace(/\.$/, '');
            return prefix ? `${prefix}.${fieldKey}` : fieldKey;
        },

        async startScanner() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopScanner();
            }

            let decoder;
            try {
                decoder = await resolveDecoderModule();
            } catch {
                this.hasError = true;
                this.errorMessage = 'Failed to load camera decoder.';
                return;
            }

            if (!this.html5Qrcode) {
                const decoderFormats = decoder.Html5QrcodeSupportedFormats;
                ensureScannerInstance(this, this.elementId, formats, decoder.Html5Qrcode, decoderFormats);
            }

            try {
                await this.html5Qrcode.start(
                    this.selectedDeviceId,
                    { fps, qrbox: this.currentQrbox() },
                    (decodedText) => {
                        this.processScan(decodedText, 'camera');
                    },
                    () => {}
                );
                this.isScanning = true;
                persistCameraId(storageKey, this.selectedDeviceId);
            } catch (err) {
                this.hasError = true;
                this.errorMessage = 'Failed to start camera feed.';
            }
        },

        async stopScanner() {
            await stopCameraFeed(this, 'sequence scanner');
        },

        processScan(decodedText, source = 'camera') {
            const currentField = this.getCurrentField();
            if (!currentField) return;

            const trimmed = sanitizeScannedValue(decodedText);
            if (!trimmed) return;

            if (serverCheck && componentKey && this.$wire) {
                return this.processServerScan(currentField, trimmed, source)
                    .finally(() => this.replayQueuedScan());
            }

            this.results[currentField.key] = trimmed;
            this.emitFeedback();

            // Sync with Livewire form state (bound by statePrefix, default `data.*`)
            // plus the component's own state path when configured, so the
            // sequence participates in getState()/dehydrate().
            if (this.$wire) {
                this.$wire.set(this.statePathFor(currentField.key), trimmed);

                if (componentStatePath) {
                    this.$wire.set(componentStatePath, { ...this.results });
                }
            }

            window.dispatchEvent(new CustomEvent('qr-sequence-step', {
                detail: {
                    field: currentField.key,
                    value: trimmed,
                    index: this.currentFieldIndex,
                    source,
                },
            }));

            // Auto-advance to next field, or stop the feed when complete
            // so the button never gets stuck on "Stop".
            if (this.currentFieldIndex < this.fields.length - 1) {
                this.currentFieldIndex++;
            } else {
                this.stopScanner();
                window.dispatchEvent(new CustomEvent('qr-sequence-completed', {
                    detail: { results: { ...this.results } },
                }));
            }
        },

        // A focused step input owns the burst: scan into that step.
        focusStepFromInput() {
            const stepKey = document.activeElement?.closest?.('[data-qr-step]')?.getAttribute('data-qr-step');
            const index = stepKey ? this.indexOfStep(stepKey) : -1;

            if (index >= 0) this.currentFieldIndex = index;
        },

        // One burst that arrives during a server check waits; any more are dropped.
        holdScan(scannedValue) {
            const code = sanitizeScannedValue(scannedValue);

            if (!code) return false;

            if (this.queuedScan) {
                window.dispatchEvent(new CustomEvent('qr-scan-dropped', {
                    detail: { value: code, field: this.getCurrentField()?.key ?? null, source: 'hardware' },
                }));

                return true;
            }

            this.queuedScan = code;

            return true;
        },

        replayQueuedScan() {
            const queued = this.queuedScan;
            this.queuedScan = null;

            if (queued) return this.processScan(queued, 'hardware');
        },

        emitFeedback() {
            // The single beep for this delivery (camera or hardware): the
            // interceptor that routed hardware bursts never beeps.
            emitScanFeedback(qrFeedback, {
                sound,
                vibrate,
                beepFrequency,
                beepDurationMs,
                vibrateDurationMs,
            });
        },

        indexOfStep(key) {
            return this.fields.findIndex((field) => field.key === key);
        },

        /**
         * One handleStepScan call per scan: the server checks the step, writes
         * state and answers with the step to show next. Scans that arrive
         * while a check is in flight are dropped.
         */
        stepOptions(key) {
            return steps.find((step) => step.key === key) ?? null;
        },

        isRepeating(key) {
            return Boolean(this.stepOptions(key)?.repeating);
        },

        // "k / n" for a counted repeat step, "k" when open-ended, null otherwise.
        stepProgress(key) {
            const options = this.stepOptions(key);

            if (!options?.repeating) return null;

            const count = Array.isArray(this.results[key]) ? this.results[key].length : 0;

            return options.repeat ? `${count} / ${options.repeat}` : `${count}`;
        },

        async finishStep(key) {
            const field = this.fields[this.indexOfStep(key)];

            if (!field || !serverCheck || !componentKey || !this.$wire) return;

            return this.processServerScan(field, '', 'manual', { method: 'finishStep' })
                .finally(() => this.replayQueuedScan());
        },

        async processServerScan(currentField, code, source, { keepStep = false, method = 'handleStepScan' } = {}) {
            if (this.isChecking) return;

            this.isChecking = true;
            let result;

            try {
                result = await this.$wire.callSchemaComponentMethod(
                    componentKey,
                    method,
                    method === 'finishStep' ? { step: currentField.key } : { step: currentField.key, code, source },
                );
            } catch (error) {
                console.error('QR sequence check failed:', error);
                result = null;
            } finally {
                this.isChecking = false;
            }

            if (result === null || typeof result !== 'object' || typeof result.passes !== 'boolean') {
                result = { passes: false, step: currentField.key, next_step: currentField.key, message: scanFailedMessage, values: { ...this.results } };
            }

            const completedValues = { ...this.results };
            (result.skipped || []).forEach((key) => delete completedValues[key]);
            if (result.passes && result.step && !result.finished) {
                const value = result.code ?? code;
                const previous = completedValues[result.step];

                completedValues[result.step] = this.isRepeating(result.step)
                    ? [...(Array.isArray(previous) ? previous : []), value]
                    : value;
            }

            this.results = { ...(result.values || {}) };

            // A rejected typed correction stays visible so the operator can fix it.
            if (!result.passes && keepStep && code !== '') this.results[currentField.key] = code;

            const nextIndex = this.indexOfStep(result.next_step);
            if (nextIndex >= 0 && !keepStep) this.currentFieldIndex = nextIndex;

            if (!result.passes) {
                this.stepError = result.message || scanFailedMessage;

                if (result.invalid_step) {
                    window.dispatchEvent(new CustomEvent('qr-sequence-invalid', {
                        detail: { field: result.invalid_step, message: this.stepError, values: { ...(result.values || {}) } },
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('qr-sequence-rejected', {
                        detail: { field: result.step ?? currentField.key, value: code, message: this.stepError, source },
                    }));
                }

                return result;
            }

            this.stepError = null;
            if (source !== 'manual') this.emitFeedback();

            if (!result.finished) window.dispatchEvent(new CustomEvent('qr-sequence-step', {
                detail: {
                    field: result.step,
                    value: result.code ?? code,
                    index: this.indexOfStep(result.step),
                    skipped: result.skipped || [],
                    source,
                },
            }));

            if (result.completed) {
                if (!result.reset) this.stopScanner();

                window.dispatchEvent(new CustomEvent('qr-sequence-completed', {
                    detail: { results: completedValues, submitted: result.submitted ?? null },
                }));
            }

            return result;
        },

        /**
         * Sync a manually typed or corrected step value to form state.
         */
        syncEditedValue(fieldKey) {
            const trimmed = ((this.results[fieldKey] || '') + '').trim();

            // Typed corrections go through the same server checks as scans.
            if (trimmed && serverCheck && componentKey && this.$wire) {
                const field = this.fields[this.indexOfStep(fieldKey)] ?? { key: fieldKey };
                this.results[fieldKey] = trimmed;

                return this.processServerScan(field, trimmed, 'manual', { keepStep: true });
            }

            if (!trimmed) {
                delete this.results[fieldKey];
            } else {
                this.results[fieldKey] = trimmed;
            }

            if (this.$wire) {
                this.$wire.set(this.statePathFor(fieldKey), trimmed);

                if (componentStatePath) {
                    this.$wire.set(componentStatePath, { ...this.results });
                }
            }

            window.dispatchEvent(new CustomEvent('qr-sequence-edited', {
                detail: {
                    field: fieldKey,
                    value: trimmed,
                },
            }));
        },

        resetSequence() {
            this.currentFieldIndex = 0;
            this.results = {};
            this.stepError = null;
        },
    };
}
