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
} = {}) {
    const storageKey = cameraStorageKey || storageKeyFor('scanner');

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
                        this.handleScanResult(scannedValue);
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
                        this.handleScanResult(decodedText);
                        this.closeScannerModal();
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

        handleScanResult(scannedText) {
            const trimmed = sanitizeScannedValue(scannedText);
            if (!trimmed) return;

            this.value = trimmed;

            // Typed input syncs through entangle + wire:model; a scan is a
            // discrete event, so it is pushed live exactly once to run
            // afterStateUpdated hooks (rejectWhen, normalizeUsing).
            const path = this.getStatePath();
            if (this.$wire && path) {
                this.$wire.set(path, trimmed);
            }

            // The single beep for this delivery (camera or hardware): the
            // interceptor that routed hardware bursts never beeps.
            emitScanFeedback(qrFeedback, {
                sound,
                vibrate,
                beepFrequency,
                beepDurationMs,
                vibrateDurationMs,
            });

            // Dispatch custom window event
            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: {
                    value: trimmed,
                    field: this.$el.getAttribute('data-field-name') || null,
                    nextField: nextField,
                },
            }));

            // Handle sequential focus transition if configured
            if (nextField) {
                this.$nextTick(() => {
                    this.advanceFocus(nextField);
                });
            }
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

            this.handleScanResult(decodedText);
            this.closeScannerModal();
        },
    };
}
