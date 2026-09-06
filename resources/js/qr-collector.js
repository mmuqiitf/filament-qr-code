import { Html5Qrcode } from 'html5-qrcode';
import { qrFeedback } from './audio-feedback.js';
import { createWedgeHandler } from './qr-wedge.js';

/**
 * Alpine component for QrCollector (batch scanning).
 */
export default function qrCollectorComponent({
    state = null,
    statePath = null,
    allowDuplicates = false,
    sound = true,
    vibrate = true,
    hardwareScanner = true,
    fps = 15,
    qrbox = 250,
    delayBetweenScansMs = 1200,
} = {}) {
    const initialItems = Array.isArray(state)
        ? state.map(item => typeof item === 'object' && item !== null && item.code ? item : { code: String(item), scanned_at: new Date().toLocaleTimeString() })
        : [];

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

        init() {
            this.elementId = `qr-collector-${this.$id('qr-col')}`;

            if (hardwareScanner) {
                this.wedgeHandler = createWedgeHandler({
                    sound,
                    vibrate,
                    onScan: (scannedValue) => {
                        this.handleDetectedCode(scannedValue);
                    },
                });

                window.addEventListener('keydown', (e) => {
                    this.wedgeHandler.handleKeyDown(e);
                });
            }

            this.loadCameras();
        },

        syncState() {
            const rawCodes = this.items.map(i => i.code);
            if (this.statePath && this.$wire) {
                this.$wire.set(this.statePath, rawCodes);
            }
        },

        async loadCameras() {
            this.isLoading = true;
            try {
                const devices = await Html5Qrcode.getCameras();
                this.devices = devices || [];
                if (this.devices.length > 0) {
                    this.selectedDeviceId = this.devices[0].id;
                }
                this.isLoading = false;
            } catch {
                this.isLoading = false;
                this.hasError = true;
                this.errorMessage = 'Camera access unavailable.';
            }
        },

        async startCollector() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopCollector();
            }

            if (!this.html5Qrcode) {
                this.html5Qrcode = new Html5Qrcode(this.elementId);
            }

            try {
                await this.html5Qrcode.start(
                    this.selectedDeviceId,
                    { fps, qrbox },
                    (decodedText) => {
                        this.handleDetectedCode(decodedText);
                    },
                    () => {}
                );
                this.isScanning = true;
            } catch (err) {
                this.hasError = true;
                this.errorMessage = 'Failed to start camera.';
            }
        },

        async stopCollector() {
            if (this.html5Qrcode && this.isScanning) {
                try {
                    await this.html5Qrcode.stop();
                } catch (e) {
                    console.debug('Error stopping collector:', e);
                } finally {
                    this.isScanning = false;
                }
            }
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

            qrFeedback.trigger({ sound, vibrate });

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
