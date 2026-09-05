import { Html5Qrcode } from 'html5-qrcode';
import { qrFeedback } from './audio-feedback.js';
import { createWedgeHandler } from './qr-wedge.js';

/**
 * Alpine component for QrScanner field.
 */
export default function qrScannerComponent({
    state = null,
    nextField = null,
    sound = true,
    vibrate = true,
    hardwareScanner = true,
    burstThresholdMs = 50,
    fps = 15,
    qrbox = 250,
    preferRearCamera = true,
} = {}) {
    return {
        // State
        value: state,
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
        torchActive: false,
        hasTorch: false,

        init() {
            this.scannerElementId = `qr-reader-${this.$id('qr-reader')}`;

            if (hardwareScanner) {
                this.wedgeHandler = createWedgeHandler({
                    burstThresholdMs,
                    sound,
                    vibrate,
                    onScan: (scannedValue) => {
                        this.handleScanResult(scannedValue);
                    },
                });

                this.$el.addEventListener('keydown', (e) => {
                    this.wedgeHandler.handleKeyDown(e);
                });
            }

            // Sync with Livewire state binding
            this.$watch('value', (newVal) => {
                if (this.$wire) {
                    this.$wire.set(this.getStatePath(), newVal);
                }
            });
        },

        getStatePath() {
            return this.$el.getAttribute('wire:model') ||
                this.$el.getAttribute('wire:model.defer') ||
                this.$el.getAttribute('wire:model.live') ||
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

                this.selectedDeviceId = this.selectPreferredCamera(this.devices);
                this.isLoading = false;
                await this.startScan();
            } catch (err) {
                this.isLoading = false;
                this.hasError = true;
                this.errorMessage = err.message || 'Failed to access camera.';
            }
        },

        selectPreferredCamera(devices) {
            if (!preferRearCamera || !devices.length) {
                return devices[0]?.id || null;
            }

            const backKeywords = ['back', 'rear', 'environment', 'camera 0', 'facing back', 'main'];
            const rearCamera = devices.find(device => {
                const label = (device.label || '').toLowerCase();
                return backKeywords.some(kw => label.includes(kw));
            });

            return rearCamera ? rearCamera.id : devices[0].id;
        },

        async startScan() {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopScan();
            }

            if (!this.html5Qrcode) {
                this.html5Qrcode = new Html5Qrcode(this.scannerElementId);
            }

            const config = {
                fps: fps,
                qrbox: qrbox,
                aspectRatio: 1.0,
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
                        // Frame scan error (no QR detected in frame), silent ignore
                    }
                );

                this.isScanning = true;
                this.checkTorchSupport();
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
                    this.torchActive = false;
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

        checkTorchSupport() {
            try {
                const capabilities = this.html5Qrcode?.getRunningTrackCameraCapabilities();
                this.hasTorch = !!(capabilities && capabilities.torchFeature().isSupported());
            } catch {
                this.hasTorch = false;
            }
        },

        handleScanResult(scannedText) {
            const trimmed = (scannedText || '').trim();
            if (!trimmed) return;

            this.value = trimmed;

            // Trigger sensory feedback
            qrFeedback.trigger({ sound, vibrate });

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
            // Find input by name, id, or wire:model
            const targetEl = document.querySelector(`[data-field-name="${targetFieldName}"] input, input[name="${targetFieldName}"], #${targetFieldName}, [wire\\:model*="${targetFieldName}"]`);
            if (targetEl) {
                targetEl.focus();
                if (typeof targetEl.select === 'function') {
                    targetEl.select();
                }
            }
        },

        scanFile(event) {
            const file = event.target.files?.[0];
            if (!file) return;

            if (!this.html5Qrcode) {
                this.html5Qrcode = new Html5Qrcode(this.scannerElementId);
            }

            this.html5Qrcode.scanFile(file, true)
                .then((decodedText) => {
                    this.handleScanResult(decodedText);
                    this.closeScannerModal();
                })
                .catch((err) => {
                    this.hasError = true;
                    this.errorMessage = 'No QR code found in selected image.';
                });
        },
    };
}
