import { Html5Qrcode } from 'html5-qrcode';
import { qrFeedback } from './audio-feedback.js';
import { createWedgeHandler } from './qr-wedge.js';
import {
    emitScanFeedback,
    ensureScannerInstance,
    loadCameraDevices,
    persistCameraId,
    resolveQrboxFor,
    scannerFormatsConfigFor,
    stopCameraFeed,
    storageKeyFor,
} from './qr-camera-core.js';

/**
 * Alpine component for QrCollector (batch scanning).
 */
export default function qrCollectorComponent({
    state = null,
    statePath = null,
    allowDuplicates = false,
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
    delayBetweenScansMs = 1200,
    cameraStorageKey = null,
} = {}) {
    const initialItems = Array.isArray(state)
        ? state.map(item => typeof item === 'object' && item !== null && item.code ? item : { code: String(item), scanned_at: new Date().toLocaleTimeString() })
        : [];

    const storageKey = cameraStorageKey || storageKeyFor('collector');

    return {
        items: initialItems,
        statePath: statePath,
        scannedSet: new Set(initialItems.map(i => i.code)),
        isScanning: false,
        isProcessing: false,
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
            this.elementId = `qr-collector-${this.$id('qr-col')}`;

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
                        this.handleDetectedCode(scannedValue);
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
            this.stopCollector();
        },

        syncState() {
            const rawCodes = this.items.map(i => i.code);
            if (this.statePath && this.$wire) {
                this.$wire.set(this.statePath, rawCodes);
            }
        },

        async loadCameras() {
            await loadCameraDevices(this, {
                Html5QrcodeClass: Html5Qrcode,
                preferRearCamera,
                storageKey,
                deniedMessage: 'Camera access unavailable.',
                fixedMessage: true,
            });
        },

        scannerFormatsConfig() {
            return scannerFormatsConfigFor(formats);
        },

        currentQrbox() {
            return resolveQrboxFor(this.elementId, qrbox, formats);
        },

        async startCollector() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopCollector();
            }

            if (!this.html5Qrcode) {
                ensureScannerInstance(this, this.elementId, formats, Html5Qrcode);
            }

            try {
                await this.html5Qrcode.start(
                    this.selectedDeviceId,
                    { fps, qrbox: this.currentQrbox() },
                    (decodedText) => {
                        this.handleDetectedCode(decodedText);
                    },
                    () => {}
                );
                this.isScanning = true;
                persistCameraId(storageKey, this.selectedDeviceId);
            } catch (err) {
                this.hasError = true;
                this.errorMessage = 'Failed to start camera.';
            }
        },

        async stopCollector() {
            await stopCameraFeed(this, 'collector');
        },

        handleDetectedCode(code) {
            const trimmed = (code || '').trim();
            if (!trimmed || this.isProcessing) return;

            if (!allowDuplicates && this.scannedSet.has(trimmed)) {
                return;
            }

            this.isProcessing = true;
            this.scannedSet.add(trimmed);
            this.items.unshift({
                code: trimmed,
                scanned_at: new Date().toLocaleTimeString(),
            });

            emitScanFeedback(qrFeedback, {
                sound,
                vibrate,
                beepFrequency,
                beepDurationMs,
                vibrateDurationMs,
            });

            // Notify Livewire if action handler or state binding exists
            if (this.$wire) {
                if (typeof this.$wire.handleCollectorScan === 'function') {
                    this.$wire.handleCollectorScan(trimmed);
                }
            }

            window.dispatchEvent(new CustomEvent('qr-collector-item-added', {
                detail: { code: trimmed },
            }));

            this.syncState();

            setTimeout(() => {
                this.isProcessing = false;
            }, delayBetweenScansMs);
        },

        removeItem(index) {
            const item = this.items[index];
            if (item) {
                this.scannedSet.delete(item.code);
                this.items.splice(index, 1);
                this.syncState();
            }
        },

        clearAll() {
            this.items = [];
            this.scannedSet.clear();
            this.syncState();
        },
    };
}
