import { qrFeedback } from './audio-feedback.js';
import { createHardwareScannerHandler, sanitizeScannedValue } from './qr-hardware-scanner.js';
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
    scanTimeoutMs = 150,
    suppressWhenGlobalListenerActive = true,
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    cameraStorageKey = null,
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

            // Sync with Livewire state binding
            this.$watch('value', (newVal) => {
                const path = this.getStatePath();
                if (this.$wire && path) {
                    this.$wire.set(path, newVal);
                }
            });
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
            const inputEl = this.$el.querySelector('input');
            return inputEl?.getAttribute('wire:model') ||
                inputEl?.getAttribute('wire:model.defer') ||
                inputEl?.getAttribute('wire:model.live') ||
                this.$el.getAttribute('wire:model') ||
                '';
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
            // Find input by data-field-name, name, id, or wire:model
            const selectors = [
                `[data-field-name="${targetFieldName}"] input`,
                `input[name="${targetFieldName}"]`,
                `textarea[name="${targetFieldName}"]`,
                `#${targetFieldName}`,
                `[wire\\:model*="${targetFieldName}"]`,
                `[name*="${targetFieldName}"]`,
            ];

            for (const selector of selectors) {
                let targetEl = null;
                try {
                    targetEl = document.querySelector(selector);
                } catch {
                    continue;
                }
                if (targetEl) {
                    targetEl.focus();
                    if (typeof targetEl.select === 'function') {
                        targetEl.select();
                    }
                    break;
                }
            }
        },

        scanFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            resolveDecoderModule().then((decoder) => {
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

                return this.html5Qrcode.scanFile(file, true);
            })
                .then((decodedText) => {
                    this.handleScanResult(decodedText);
                    this.closeScannerModal();
                })
                .catch(() => {
                    this.hasError = true;
                    this.errorMessage = 'No barcode or QR code found in selected image.';
                });
        },
    };
}
