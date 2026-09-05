import { Html5Qrcode } from 'html5-qrcode';
import { qrFeedback } from './audio-feedback.js';
import { createWedgeHandler } from './qr-wedge.js';

/**
 * Alpine component for QrScanSequence container.
 */
export default function qrScanSequenceComponent({
    fields = [],
    sound = true,
    vibrate = true,
    hardwareScanner = true,
    fps = 15,
    qrbox = 250,
} = {}) {
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

        init() {
            this.elementId = `qr-sequence-${this.$id('qr-seq')}`;

            if (hardwareScanner) {
                this.wedgeHandler = createWedgeHandler({
                    sound,
                    vibrate,
                    onScan: (scannedValue) => {
                        this.processScan(scannedValue);
                    },
                });

                window.addEventListener('keydown', (e) => {
                    this.wedgeHandler.handleKeyDown(e);
                });
            }

            this.loadCameras();
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
                    this.selectedDeviceId = this.devices[0].id;
                }

                this.isLoading = false;
            } catch (err) {
                this.isLoading = false;
                this.hasError = true;
                this.errorMessage = 'Camera access denied or unavailable.';
            }
        },

        async startScanner() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopScanner();
            }

            if (!this.html5Qrcode) {
                this.html5Qrcode = new Html5Qrcode(this.elementId);
            }

            try {
                await this.html5Qrcode.start(
                    this.selectedDeviceId,
                    { fps, qrbox },
                    (decodedText) => {
                        this.processScan(decodedText);
                    },
                    () => {}
                );
                this.isScanning = true;
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
            qrFeedback.trigger({ sound, vibrate });

            // Sync with Livewire state if available
            if (this.$wire) {
                this.$wire.set(`data.${currentField.key}`, trimmed);
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
