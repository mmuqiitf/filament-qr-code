import { qrFeedback } from './audio-feedback.js';
import { sanitizeScannedValue } from './qr-hardware-scanner.js';
import { inputRouter } from './input-router.js';
import { findFieldInput, readWireModelPath } from './field-locator.js';
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
import { createScanDeduper, requestScanCheck } from './scan-pipeline.js';
import { FLASH_CLASSES, flashElement, playOutcomeFeedback } from './feedback.js';

/**
 * Alpine component for QrScanner field.
 */
export default function qrScannerComponent({
    state = null,
    statePath = null,
    nextField = null,
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
    hardwareScope = 'focus',
    preventSubmit = true,
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    cameraStorageKey = null,
    fileScanFailedMessage = 'No barcode or QR code found in selected image.',
    insecureMessage = 'Camera needs a secure context: serve this page over https or open it on localhost, then allow camera access.',
    serverCheck = false,
    componentKey = null,
    cooldownMs = 0,
    clearOnReject = true,
    scanFailedMessage = 'The scan could not be checked. Please try again.',
    successFeedback = null,
    errorFeedback = null,
    mode = 'single',
    continuousDisplay = 'empty',
    pauseOnReject = false,
    keepCameraOpen = false,
} = {}) {
    const storageKey = cameraStorageKey || storageKeyFor('scanner');
    const deduper = createScanDeduper();
    const isContinuous = mode === 'continuous';

    return {
        // State
        value: state,
        statePath: statePath,
        isModalOpen: false,
        pendingFlash: null,
        isScanning: false,
        isLoading: false,
        hasError: false,
        errorMessage: '',
        devices: [],
        selectedDeviceId: null,
        html5Qrcode: null,
        scannerElementId: '',
        unregisterTarget: null,
        isChecking: false,
        isPaused: false,
        rejectionMessage: '',
        queuedScan: null,
        boundDeliverHandler: null,

        init() {
            this.scannerElementId = `qr-reader-${this.$id('qr-reader')}`;

            if (hardwareScanner) {
                this.unregisterTarget = inputRouter().register({
                    id: `${this.scannerElementId}-${Math.random().toString(36).slice(2)}`,
                    element: this.fieldElement(),
                    name: this.fieldElement().getAttribute('data-field-name') || null,
                    kind: 'field',
                    scope: hardwareScope === 'page' ? 'page' : 'focus',
                    detection: { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs, preventSubmit },
                    accept: () => !this.isChecking && !this.isPaused,
                    hold: (scannedValue) => this.holdScan(scannedValue, 'hardware'),
                    isModalOpen: () => this.isModalOpen,
                    deliver: (scannedValue) => {
                        this.handleScanResult(scannedValue, 'hardware');
                    },
                });
            }

            this.boundDeliverHandler = (event) => {
                if (!serverCheck || !componentKey) {
                    return;
                }

                event.preventDefault();
                this.handleScanResult(event.detail?.value, 'hardware');
            };
            this.fieldElement().addEventListener('qr-scan-deliver', this.boundDeliverHandler);
        },

        destroy() {
            if (this.boundDeliverHandler) {
                this.fieldElement().removeEventListener('qr-scan-deliver', this.boundDeliverHandler);
                this.boundDeliverHandler = null;
            }

            this.unregisterTarget?.();
            this.unregisterTarget = null;
            this.stopScan();
        },

        getStatePath() {
            if (this.statePath) {
                return this.statePath;
            }

            return readWireModelPath(this.fieldElement().querySelector('input')) || readWireModelPath(this.fieldElement()) || '';
        },

        openScannerModal() {
            this.isModalOpen = true;
            this.hasError = false;
            this.errorMessage = '';
            this.$nextTick(() => {
                this.loadCamerasAndStart();
            });
        },

        // In Alpine, $el is the element whose directive called the method (e.g. the hidden file input);
        // $root is the x-data field wrapper.
        fieldElement() {
            return this.$root ?? this.$el;
        },

        closeScannerModal() {
            this.stopScan().then(() => {
                this.isModalOpen = false;

                const outcome = this.pendingFlash;
                this.pendingFlash = null;

                if (outcome) {
                    this.$nextTick(() => flashElement(this.fieldElement(), FLASH_CLASSES[outcome]));
                }
            });
        },

        // The modal overlay hides the field, so flash the panel now and the field once the modal closes.
        flashTargetFor(outcome, settings) {
            if (!this.isModalOpen) {
                return this.fieldElement();
            }

            if (settings?.flash) {
                this.pendingFlash = outcome;
            }

            return this.fieldElement().querySelector('[data-qr-scanner-panel]');
        },

        async loadCamerasAndStart() {
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
                requireDevices: true,
                emptyMessage: 'No camera devices detected on this system.',
                deniedMessage: 'Failed to access camera.',
                insecureMessage,
            });

            if (!this.hasError) {
                await this.startScan();
            }
        },

        scannerFormatsConfig() {
            return scannerFormatsConfigFor(formats);
        },

        currentQrbox() {
            return resolveQrboxFor(this.scannerElementId, qrbox, formats);
        },

        async startScan() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopScan();
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
                ensureScannerInstance(
                    this,
                    this.scannerElementId,
                    formats,
                    decoder.Html5Qrcode,
                    decoderFormats
                );
            }

            const config = {
                fps: fps,
                qrbox: this.currentQrbox(),
            };

            try {
                await this.html5Qrcode.start(
                    this.selectedDeviceId,
                    config,
                    (decodedText) => {
                        this.onCameraDecode(decodedText);
                    },
                    () => {
                        // Frame scan error (no QR/barcode detected in frame), silent ignore
                    }
                );

                this.isScanning = true;
                persistCameraId(storageKey, this.selectedDeviceId);
            } catch (err) {
                this.hasError = true;
                this.errorMessage = err.message || 'Error starting camera scanner.';
            }
        },

        async stopScan() {
            await stopCameraFeed(this, 'camera scanner');
        },

        async handleScanResult(scannedText, source = 'camera') {
            const code = sanitizeScannedValue(scannedText);

            if (!code || this.isChecking || this.isPaused) {
                return { passes: false, ignored: true };
            }

            const field = this.fieldElement().getAttribute('data-field-name') || null;

            if (deduper.isDuplicate(code, cooldownMs)) {
                window.dispatchEvent(new CustomEvent('qr-scan-duplicate', { detail: { value: code, field, source, mode } }));

                return { passes: false, ignored: true };
            }

            if (!serverCheck || !componentKey || !this.$wire) {
                this.acceptLocally(code, source, field);

                return { passes: true, code, source };
            }

            this.isChecking = true;

            try {
                const result = await requestScanCheck(this.$wire, { componentKey, code, source, scanFailedMessage });

                if (result.passes) {
                    this.applyAccepted(result, field);
                } else {
                    this.applyRejected(result, field);
                }

                return result;
            } finally {
                this.isChecking = false;
                this.replayQueuedScan();
            }
        },

        // Continuous mode keeps one burst that arrived during a check; any more are dropped.
        holdScan(scannedText, source = 'hardware') {
            const code = sanitizeScannedValue(scannedText);

            if (!isContinuous || this.isPaused || !code) {
                return false;
            }

            if (this.queuedScan) {
                this.dispatchDropped(code, source);

                return true;
            }

            this.queuedScan = { code, source };

            return true;
        },

        replayQueuedScan() {
            const queued = this.queuedScan;
            this.queuedScan = null;

            if (!queued) {
                return;
            }

            if (this.isPaused) {
                this.dispatchDropped(queued.code, queued.source);

                return;
            }

            this.$nextTick(() => this.handleScanResult(queued.code, queued.source));
        },

        dispatchDropped(code, source) {
            window.dispatchEvent(new CustomEvent('qr-scan-dropped', {
                detail: { value: code, field: this.fieldName(), source },
            }));
        },

        fieldName() {
            return this.fieldElement().getAttribute('data-field-name') || null;
        },

        resumeScanning() {
            if (!this.isPaused) {
                return;
            }

            this.isPaused = false;
            this.rejectionMessage = '';
            window.dispatchEvent(new CustomEvent('qr-scan-resumed', { detail: { field: this.fieldName() } }));

            if (this.isModalOpen) {
                this.resumeCamera();
            }
        },

        async onCameraDecode(decodedText) {
            if (!serverCheck || !componentKey) {
                await this.handleScanResult(decodedText, 'camera');
                this.closeScannerModal();

                return;
            }

            if (this.isChecking || this.isPaused) {
                return;
            }

            this.pauseCamera();

            const result = await this.handleScanResult(decodedText, 'camera');

            if (result.ignored) {
                this.resumeCamera();

                return;
            }

            if (result.passes) {
                if (isContinuous && keepCameraOpen) {
                    this.resumeCamera();

                    return;
                }

                this.closeScannerModal();

                return;
            }

            if (!this.isPaused) {
                this.resumeCamera();
            }
        },

        pauseCamera() {
            try {
                this.html5Qrcode?.pause?.(true);
            } catch (error) {
                console.debug('QR camera pause failed:', error);
            }
        },

        resumeCamera() {
            this.pendingFlash = null;

            try {
                this.html5Qrcode?.resume?.();
            } catch (error) {
                console.debug('QR camera resume failed:', error);
            }
        },

        acceptLocally(code, source, field) {
            this.value = code;

            const path = this.getStatePath();
            if (this.$wire && path) {
                this.$wire.set(path, code);
            }

            emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });

            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: { value: code, field, nextField, source, mode },
            }));
            this.announceListenerDelivery(code, source, field);

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        applyAccepted({ code, source, recordKey }, field) {
            // The server already wrote the state; this keeps the deferred entangle in step without a request.
            this.value = isContinuous && continuousDisplay === 'empty' ? '' : code;
            this.rejectionMessage = '';

            if (successFeedback) {
                playOutcomeFeedback('success', successFeedback, {
                    element: this.flashTargetFor('success', successFeedback),
                    beepFrequency,
                    beepDurationMs,
                });
            } else {
                emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });
            }

            window.dispatchEvent(new CustomEvent('qr-scan-accepted', { detail: { value: code, field, source, recordKey, mode } }));
            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: { value: code, field, nextField, source, recordKey, mode },
            }));
            this.announceListenerDelivery(code, source, field);

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        // Before the router, a page listener wrote hardware scans into this field and
        // announced them with qr-hardware-scanned. Keep that event for listener pages.
        announceListenerDelivery(code, source, field) {
            if (source !== 'hardware' || !inputRouter().hasTargetOfKind('listener')) {
                return;
            }

            window.dispatchEvent(new CustomEvent('qr-hardware-scanned', { detail: { value: code, field } }));
        },

        applyRejected({ code, source, message }, field) {
            if (clearOnReject) {
                this.value = '';
            }

            if (isContinuous) {
                this.rejectionMessage = message ?? '';
            }

            if (isContinuous && pauseOnReject) {
                this.isPaused = true;
                window.dispatchEvent(new CustomEvent('qr-scan-paused', { detail: { field } }));
            }

            playOutcomeFeedback('error', errorFeedback, {
                element: this.flashTargetFor('error', errorFeedback),
                beepFrequency,
                beepDurationMs,
            });

            window.dispatchEvent(new CustomEvent('qr-scan-rejected', { detail: { value: code, field, source, message, mode } }));
        },

        advanceFocus(targetFieldName) {
            const targetElement = findFieldInput(targetFieldName);

            if (!targetElement) {
                return;
            }

            targetElement.focus();

            if (typeof targetElement.select === 'function') {
                targetElement.select();
            }
        },

        async scanFile(event) {
            const input = event.target;
            const file = input.files?.[0];

            // Reset so picking the same file again still fires `change`.
            input.value = '';

            if (!file) return;

            this.hasError = false;
            this.errorMessage = '';

            let decoder;
            try {
                decoder = await resolveDecoderModule();
            } catch {
                this.hasError = true;
                this.errorMessage = 'Failed to load camera decoder.';
                return;
            }

            if (!this.html5Qrcode) {
                ensureScannerInstance(
                    this,
                    this.scannerElementId,
                    formats,
                    decoder.Html5Qrcode,
                    decoder.Html5QrcodeSupportedFormats
                );
            }

            // html5-qrcode refuses a file scan on an instance that is
            // streaming the camera, so pause the feed for the decode.
            const resumeCamera = this.isScanning;
            await this.stopScan();

            let decodedText;
            try {
                decodedText = await this.html5Qrcode.scanFile(file, false);
            } catch (error) {
                console.debug('QR file scan failed:', error);
                this.hasError = true;
                this.errorMessage = fileScanFailedMessage;

                if (resumeCamera && this.isModalOpen) {
                    await this.startScan();
                }

                return;
            }

            await this.handleScanResult(decodedText, 'upload');
            this.closeScannerModal();
        },
    };
}
