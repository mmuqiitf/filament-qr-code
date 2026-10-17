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
                    deliver: (scannedValue) => {
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
                return this.processServerScan(currentField, trimmed, source);
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
        async processServerScan(currentField, code, source, { keepStep = false } = {}) {
            if (this.isChecking) return;

            this.isChecking = true;
            let result;

            try {
                result = await this.$wire.callSchemaComponentMethod(componentKey, 'handleStepScan', {
                    step: currentField.key,
                    code,
                    source,
                });
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
            if (result.passes && result.step) completedValues[result.step] = result.code ?? code;

            this.results = { ...(result.values || {}) };

            // A rejected typed correction stays visible so the operator can fix it.
            if (!result.passes && keepStep) this.results[currentField.key] = code;

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

            window.dispatchEvent(new CustomEvent('qr-sequence-step', {
                detail: {
                    field: result.step,
                    value: result.code ?? code,
                    index: this.indexOfStep(result.step),
                    skipped: result.skipped || [],
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
