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
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    statePrefix = 'data',
    editable = true,
    cameraStorageKey = null,
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
        wedgeHandler: null,
        boundWedgeHandler: null,

        init() {
            this.elementId = `qr-sequence-${this.$id('qr-seq')}`;

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
                        this.processScan(scannedValue);
                    },
                });

                this.boundWedgeHandler = (e) => {
                    this.wedgeHandler.handleKeyDown(e);
                };
                window.addEventListener('keydown', this.boundWedgeHandler);
            }

            this.loadCameras();
        },

        destroy() {
            if (this.boundWedgeHandler) {
                window.removeEventListener('keydown', this.boundWedgeHandler);
                this.boundWedgeHandler = null;
            }
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
            this.isLoading = true;
            this.hasError = false;

            try {
                const devices = await Html5Qrcode.getCameras();
                this.devices = devices || [];

                if (this.devices.length > 0) {
                    this.selectedDeviceId = selectPreferredCamera(this.devices, {
                        preferRear: preferRearCamera,
                        storageKey,
                    });
                }

                this.isLoading = false;
            } catch (err) {
                this.isLoading = false;
                this.hasError = true;
                this.errorMessage = 'Camera access denied or unavailable.';
            }
        },

        scannerFormatsConfig() {
            const mapped = mapFormats(formats);
            return mapped.length > 0 ? { formatsToSupport: mapped } : {};
        },

        currentQrbox() {
            const container = document.getElementById(this.elementId);
            const box = computeQrboxForElement(container, qrbox, formats);
            syncReticleToQrbox(container, box);

            return box;
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

            if (!this.html5Qrcode) {
                this.html5Qrcode = new Html5Qrcode(this.elementId, this.scannerFormatsConfig());
            }

            try {
                await this.html5Qrcode.start(
                    this.selectedDeviceId,
                    { fps, qrbox: this.currentQrbox() },
                    (decodedText) => {
                        this.processScan(decodedText);
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
            if (this.html5Qrcode && this.isScanning) {
                try {
                    await this.html5Qrcode.stop();
                } catch (e) {
                    console.debug('Error stopping sequence scanner:', e);
                } finally {
                    this.isScanning = false;
                }
            }
        },

        processScan(decodedText) {
            const currentField = this.getCurrentField();
            if (!currentField) return;

            const trimmed = decodedText.trim();
            if (!trimmed) return;

            this.results[currentField.key] = trimmed;
            triggerFeedback(qrFeedback, {
                sound,
                vibrate,
                frequency: beepFrequency,
                duration: beepDurationMs,
                vibrateDuration: vibrateDurationMs,
            });

            // Sync with Livewire form state (bound by statePrefix, default `data.*`)
            if (this.$wire) {
                this.$wire.set(this.statePathFor(currentField.key), trimmed);
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

        /**
         * Sync a manually typed or corrected step value to form state.
         */
        syncEditedValue(fieldKey) {
            const trimmed = ((this.results[fieldKey] || '') + '').trim();

            if (!trimmed) {
                delete this.results[fieldKey];
            } else {
                this.results[fieldKey] = trimmed;
            }

            if (this.$wire) {
                this.$wire.set(this.statePathFor(fieldKey), trimmed);
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
        },
    };
}
