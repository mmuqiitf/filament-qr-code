import { Html5Qrcode } from 'html5-qrcode';
import { qrFeedback } from './audio-feedback.js';
import { createWedgeHandler } from './qr-wedge.js';
import {
    applyZoomLevel,
    computeQrboxForElement,
    getZoomState,
    hasTorchSupport,
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
    cameraStorageKey = null,
} = {}) {
    const storageKey = cameraStorageKey || storageKeyFor('sequence');

    return {
        fields: fields, // array of { key: string, label: string }
        currentFieldIndex: 0,
        results: {},
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
        torchActive: false,
        hasTorch: false,
        zoomMin: 1,
        zoomMax: 5,
        zoomValue: 1,
        hasZoom: false,

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
                this.hasTorch = hasTorchSupport(this.html5Qrcode);
                const zoom = getZoomState(this.html5Qrcode);
                if (zoom) {
                    this.hasZoom = true;
                    this.zoomMin = zoom.min;
                    this.zoomMax = zoom.max;
                    this.zoomValue = zoom.min;
                }
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
                    this.torchActive = false;
                    this.hasZoom = false;
                }
            }
        },

        async toggleTorch() {
            if (!this.html5Qrcode || !this.isScanning) return;

            try {
                const capabilities = this.html5Qrcode.getRunningTrackCameraCapabilities();
                if (capabilities && capabilities.torchFeature().isSupported()) {
                    this.torchActive = !this.torchActive;
                    await capabilities.torchFeature().apply(this.torchActive);
                }
            } catch (e) {
                console.debug('Torch toggle error:', e);
            }
        },

        async onZoomInput() {
            await applyZoomLevel(this.html5Qrcode, this.zoomValue);
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

            // Auto-advance to next field
            if (this.currentFieldIndex < this.fields.length - 1) {
                this.currentFieldIndex++;
            } else {
                window.dispatchEvent(new CustomEvent('qr-sequence-completed', {
                    detail: { results: { ...this.results } },
                }));
            }
        },

        resetSequence() {
            this.currentFieldIndex = 0;
            this.results = {};
        },
    };
}
