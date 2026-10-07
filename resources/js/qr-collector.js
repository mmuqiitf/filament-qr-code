import { qrFeedback } from './audio-feedback.js';
import { createHardwareScannerHandler } from './qr-hardware-scanner.js';
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
    scanTimeoutMs = 150,
    suppressWhenGlobalListenerActive = true,
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    delayBetweenScansMs = 1200,
    cameraStorageKey = null,
    insecureMessage = 'Camera needs a secure context: serve this page over https or open it on localhost, then allow camera access.',
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
        hardwareScannerHandler: null,
        boundHardwareScannerHandler: null,

        init() {
            this.elementId = `qr-collector-${this.$id('qr-col')}`;

            if (hardwareScanner) {
                this.hardwareScannerHandler = createHardwareScannerHandler({
                    burstThresholdMs,
                    minBarcodeLength,
                    terminators,
                    scanTimeoutMs,
                    suppressWhenGlobalListenerActive,
                    sound,
                    vibrate,
                    beepFrequency,
                    beepDurationMs,
                    vibrateDurationMs,
                    onScan: (scannedValue) => {
                        this.handleDetectedCode(scannedValue);
                    },
                });

                this.boundHardwareScannerHandler = (e) => {
                    this.hardwareScannerHandler.handleKeyDown(e);
                };
                window.addEventListener('keydown', this.boundHardwareScannerHandler);
            }

            this.loadCameras();
        },

        destroy() {
            if (this.boundHardwareScannerHandler) {
                window.removeEventListener('keydown', this.boundHardwareScannerHandler);
                this.boundHardwareScannerHandler = null;
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
                deniedMessage: 'Camera access unavailable.',
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

        async startCollector() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopCollector();
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
