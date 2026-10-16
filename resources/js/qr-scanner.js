import { qrFeedback } from './audio-feedback.js';
import { createHardwareScannerHandler, sanitizeScannedValue } from './qr-hardware-scanner.js';
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
    suppressWhenGlobalListenerActive = true,
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
} = {}) {
    const storageKey = cameraStorageKey || storageKeyFor('scanner');
    const deduper = createScanDeduper();

    return {
        // State
        value: state,
        statePath: statePath,
        isModalOpen: false,
        isScanning: false,
        isLoading: false,
        hasError: false,
        errorMessage: '',
        devices: [],
        selectedDeviceId: null,
        html5Qrcode: null,
        scannerElementId: '',
        hardwareScannerHandler: null,
        isChecking: false,
        boundHardwareScannerHandler: null,

        init() {
            this.scannerElementId = `qr-reader-${this.$id('qr-reader')}`;

            if (hardwareScanner) {
                this.hardwareScannerHandler = createHardwareScannerHandler({
                    burstThresholdMs,
                    minBarcodeLength,
                    minFastKeys,
                    terminators,
                    scanTimeoutMs,
                    suppressWhenGlobalListenerActive,
                    onScan: (scannedValue) => {
                        this.handleScanResult(scannedValue, 'hardware');
                    },
                });

                // Field-scoped on purpose: pair with QrHardwareScannerListener for
                // page-global cashier capture to avoid double handling.
                this.boundHardwareScannerHandler = (e) => {
                    this.hardwareScannerHandler.handleKeyDown(e);
                };
                this.$el.addEventListener('keydown', this.boundHardwareScannerHandler);
            }
        },

        destroy() {
            if (this.boundHardwareScannerHandler) {
                this.$el.removeEventListener('keydown', this.boundHardwareScannerHandler);
                this.boundHardwareScannerHandler = null;
            }
            this.stopScan();
        },

        getStatePath() {
            if (this.statePath) {
                return this.statePath;
            }

            return readWireModelPath(this.$el.querySelector('input')) || readWireModelPath(this.$el) || '';
        },

        openScannerModal() {
            this.isModalOpen = true;
            this.hasError = false;
            this.errorMessage = '';
            this.$nextTick(() => {
                this.loadCamerasAndStart();
            });
        },

        closeScannerModal() {
            this.stopScan().then(() => {
                this.isModalOpen = false;
            });
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

            if (!code || this.isChecking) {
                return { passes: false, ignored: true };
            }

            const field = this.$el.getAttribute('data-field-name') || null;

            if (deduper.isDuplicate(code, cooldownMs)) {
                window.dispatchEvent(new CustomEvent('qr-scan-duplicate', { detail: { value: code, field, source } }));

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
            }
        },

        async onCameraDecode(decodedText) {
            if (!serverCheck || !componentKey) {
                await this.handleScanResult(decodedText, 'camera');
                this.closeScannerModal();

                return;
            }

            if (this.isChecking) {
                return;
            }

            this.pauseCamera();

            const result = await this.handleScanResult(decodedText, 'camera');

            if (result.ignored) {
                this.resumeCamera();

                return;
            }

            if (result.passes) {
                this.closeScannerModal();

                return;
            }

            this.resumeCamera();
        },

        pauseCamera() {
            try {
                this.html5Qrcode?.pause?.(true);
            } catch (error) {
                console.debug('QR camera pause failed:', error);
            }
        },

        resumeCamera() {
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
                detail: { value: code, field, nextField, source },
            }));

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        applyAccepted({ code, source, recordKey }, field) {
            // The server already wrote the state; this keeps the deferred entangle in step without a request.
            this.value = code;

            emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });

            window.dispatchEvent(new CustomEvent('qr-scan-accepted', { detail: { value: code, field, source, recordKey } }));
            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: { value: code, field, nextField, source, recordKey },
            }));

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        applyRejected({ code, source, message }, field) {
            if (clearOnReject) {
                this.value = '';
            }

            window.dispatchEvent(new CustomEvent('qr-scan-rejected', { detail: { value: code, field, source, message } }));
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
