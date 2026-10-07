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
    scanTimeoutMs = 150,
    suppressWhenGlobalListenerActive = true,
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    statePrefix = 'data',
    componentStatePath = null,
    editable = true,
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
        hardwareScannerHandler: null,
        boundHardwareScannerHandler: null,

        init() {
            this.elementId = `qr-sequence-${this.$id('qr-seq')}`;

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
                        this.processScan(scannedValue);
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
            await stopCameraFeed(this, 'sequence scanner');
        },

        processScan(decodedText) {
            const currentField = this.getCurrentField();
            if (!currentField) return;

            const trimmed = decodedText.trim();
            if (!trimmed) return;

            this.results[currentField.key] = trimmed;
            emitScanFeedback(qrFeedback, {
                sound,
                vibrate,
                beepFrequency,
                beepDurationMs,
                vibrateDurationMs,
            });

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
        },
    };
}
