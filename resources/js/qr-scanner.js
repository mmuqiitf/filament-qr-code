import { Html5Qrcode } from 'html5-qrcode';
import { qrFeedback } from './audio-feedback.js';
import { createWedgeHandler } from './qr-wedge.js';
import {
    computeQrboxForElement,
    mapFormats,
    persistCameraId,
    selectPreferredCamera,
    storageKeyFor,
    syncReticleToQrbox,
    triggerFeedback,
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
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    cameraStorageKey = null,
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
        wedgeHandler: null,
        boundWedgeHandler: null,

        init() {
            this.scannerElementId = `qr-reader-${this.$id('qr-reader')}`;

            if (hardwareScanner) {
                this.wedgeHandler = createWedgeHandler({
                    burstThresholdMs,
                    minBarcodeLength,
                    terminators,
                    sound,
                    vibrate,
                    beepFrequency,
                    beepDurationMs,
                    vibrateDurationMs,
                    onScan: (scannedValue) => {
                        this.handleScanResult(scannedValue);
                    },
                });

                // Field-scoped on purpose: pair with QrWedgeListener for
                // page-global cashier capture to avoid double handling.
                this.boundWedgeHandler = (e) => {
                    this.wedgeHandler.handleKeyDown(e);
                };
                this.$el.addEventListener('keydown', this.boundWedgeHandler);
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
            if (this.boundWedgeHandler) {
                this.$el.removeEventListener('keydown', this.boundWedgeHandler);
                this.boundWedgeHandler = null;
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
            this.isLoading = true;
            this.hasError = false;

            try {
                const devices = await Html5Qrcode.getCameras();
                this.devices = devices || [];

                if (!this.devices.length) {
                    throw new Error('No camera devices detected on this system.');
                }

                this.selectedDeviceId = selectPreferredCamera(this.devices, {
                    preferRear: preferRearCamera,
                    storageKey,
                });
                this.isLoading = false;
                await this.startScan();
            } catch (err) {
                this.isLoading = false;
                this.hasError = true;
                this.errorMessage = err.message || 'Failed to access camera.';
            }
        },

        scannerFormatsConfig() {
            const mapped = mapFormats(formats);
            return mapped.length > 0 ? { formatsToSupport: mapped } : {};
        },

        currentQrbox() {
            const container = document.getElementById(this.scannerElementId);
            const box = computeQrboxForElement(container, qrbox, formats);
            syncReticleToQrbox(container, box);

            return box;
        },

        async startScan() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopScan();
            }

            if (!this.html5Qrcode) {
                this.html5Qrcode = new Html5Qrcode(this.scannerElementId, this.scannerFormatsConfig());
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
            if (this.html5Qrcode && this.isScanning) {
                try {
                    await this.html5Qrcode.stop();
                } catch (e) {
                    console.debug('Scanner stop error:', e);
                } finally {
                    this.isScanning = false;
                }
            }
        },

        handleScanResult(scannedText) {
            const trimmed = (scannedText || '').trim();
            if (!trimmed) return;

            this.value = trimmed;

            const path = this.getStatePath();
            if (this.$wire && path) {
                this.$wire.set(path, trimmed);
            }

            // Trigger sensory feedback
            triggerFeedback(qrFeedback, {
                sound,
                vibrate,
                frequency: beepFrequency,
                duration: beepDurationMs,
                vibrateDuration: vibrateDurationMs,
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

            if (!this.html5Qrcode) {
                this.html5Qrcode = new Html5Qrcode(this.scannerElementId, this.scannerFormatsConfig());
            }

            this.html5Qrcode.scanFile(file, true)
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
