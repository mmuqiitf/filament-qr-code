import { qrFeedback } from './audio-feedback.js';
import { sanitizeScannedValue } from './qr-hardware-scanner.js';
import { inputRouter } from './input-router.js';
import { announcerMixin, formatMessage } from './announcer.js';
import { messagesFrom } from './messages.js';
import {
    emitScanFeedback,
    ensureScannerInstance,
    loadCameraDevices,
    persistCameraId,
    resolveDecoderModule,
    cameraControlsMixin,
    refreshCameraControls,
    resolveQrboxFor,
    scannerFormatsConfigFor,
    stopCameraFeed,
    startCameraFeed,
    watchPageVisibility,
    currentCameraRun,
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
    minFastKeys = 3,
    scanTimeoutMs = 150,
    fps = 25,
    qrbox = 250,
    preferRearCamera = true,
    formats = [],
    cameraControls = true,
    delayBetweenScansMs = 1200,
    cameraStorageKey = null,
    autoStart = false,
    wireScanHandler = false,
    ...messageOptions
} = {}) {
    const messages = messagesFrom(messageOptions);
    const initialItems = Array.isArray(state)
        ? state.map(item => typeof item === 'object' && item !== null && item.code ? item : { code: String(item), scanned_at: new Date().toLocaleTimeString() })
        : [];

    const storageKey = cameraStorageKey || storageKeyFor('collector');

    return {
        ...cameraControlsMixin(),
        ...announcerMixin(),
        items: initialItems,
        lastAddedCode: null,
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
        unregisterTarget: null,

        init() {
            this.elementId = `qr-collector-${this.$id('qr-col')}`;

            this.stopWatchingVisibility = watchPageVisibility(this, {
                stop: () => this.stopCollector(),
                resume: () => this.startCollector(),
            });

            if (hardwareScanner) {
                this.unregisterTarget = inputRouter().register({
                    id: `collector-${this.elementId}-${Math.random().toString(36).slice(2)}`,
                    element: this.$root ?? this.$el,
                    name: null,
                    kind: 'collector',
                    scope: 'page',
                    detection: { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs },
                    deliver: (scannedValue) => {
                        this.handleDetectedCode(scannedValue);
                    },
                });
            }

            this.loadCameras();
        },

        destroy() {
            this.stopWatchingVisibility?.();
            this.stopWatchingVisibility = null;
            this.unregisterTarget?.();
            this.unregisterTarget = null;
            this.stopCollector();
        },

        syncState() {
            const rawCodes = this.items.map(i => i.code);
            if (this.statePath && this.$wire) {
                this.$wire.set(this.statePath, rawCodes);
            }
        },

        async loadCameras({ start = autoStart } = {}) {
            // A destroy while devices load bumps the run: skip the auto-start.
            const run = currentCameraRun(this);
            let decoder;
            try {
                decoder = await resolveDecoderModule(formats);
            } catch {
                this.hasError = true;
                this.errorMessage = messages.decoderFailedMessage;
                return;
            }

            await loadCameraDevices(this, {
                Html5QrcodeClass: decoder.Html5Qrcode,
                preferRearCamera,
                storageKey,
                deniedMessage: messages.cameraUnavailableMessage,
                emptyMessage: messages.noCamerasMessage,
                insecureMessage: messages.insecureMessage,
                fixedMessage: true,
            });

            if (start && !this.hasError) {
                await this.startCollector({ reloaded: true, run });
            }
        },

        // Retry button of the shared camera-status overlay.
        async retryCamera() {
            this.hasError = false;
            this.errorMessage = '';
            await this.loadCameras({ start: false });

            if (this.selectedDeviceId && !this.hasError) {
                await this.startCollector();
            }
        },

        scannerFormatsConfig() {
            return scannerFormatsConfigFor(formats);
        },

        currentQrbox() {
            return resolveQrboxFor(this.elementId, qrbox, formats);
        },

        async startCollector({ reloaded = false, run = null } = {}) {
            // Start pressed before the permission prompt resolved: load the
            // cameras now instead of silently doing nothing.
            if (!this.selectedDeviceId && !reloaded) {
                await this.loadCameras({ start: false });
            }

            if (!this.selectedDeviceId) {
                if (!this.hasError) {
                    this.hasError = true;
                    this.errorMessage = messages.cameraUnavailableMessage;
                }

                return;
            }

            if (this.isScanning) {
                await this.stopCollector();
            }

            run ??= currentCameraRun(this);

            let decoder;
            try {
                decoder = await resolveDecoderModule(formats);
            } catch {
                this.hasError = true;
                this.errorMessage = messages.decoderFailedMessage;
                return;
            }

            await this.waitForViewfinderLayout();

            if (!this.html5Qrcode) {
                const decoderFormats = decoder.Html5QrcodeSupportedFormats;
                ensureScannerInstance(this, this.elementId, formats, decoder.Html5Qrcode, decoderFormats);
            }

            try {
                if (!(await startCameraFeed(this, () => this.html5Qrcode.start(
                    this.selectedDeviceId,
                    { fps, qrbox: this.currentQrbox() },
                    (decodedText) => {
                        this.handleDetectedCode(decodedText);
                    },
                    () => {}
                ), run))) {
                    return;
                }
                refreshCameraControls(this, cameraControls);
                persistCameraId(storageKey, this.selectedDeviceId);
            } catch (err) {
                console.debug('Collector camera start failed:', err);
                this.hasError = true;
                this.errorMessage = messages.cameraStartFailedMessage;
            }
        },

        // html5-qrcode sizes the <video> from the container's width at start.
        // Inside an opening action modal that width is still 0, which leaves a
        // zero-sized, invisible feed, so wait (bounded) for real layout first.
        async waitForViewfinderLayout(timeoutMs = 2000) {
            const element = document.getElementById(this.elementId);
            const deadline = Date.now() + timeoutMs;

            while (element && element.clientWidth === 0 && Date.now() < deadline) {
                await new Promise((resolve) => requestAnimationFrame(resolve));
            }
        },

        async stopCollector() {
            await stopCameraFeed(this, 'collector');
        },

        handleDetectedCode(code) {
            const trimmed = sanitizeScannedValue(code);
            if (!trimmed || this.isProcessing) return;

            if (!allowDuplicates && this.scannedSet.has(trimmed)) {
                // The code just added is usually still in view: not a new rejection.
                if (trimmed !== this.lastAddedCode) {
                    this.announce(formatMessage(messages.rejectedAnnouncement, { reason: messages.duplicateMessage }));
                }

                return;
            }

            this.isProcessing = true;
            this.scannedSet.add(trimmed);
            this.items.unshift({
                code: trimmed,
                scanned_at: new Date().toLocaleTimeString(),
            });
            this.lastAddedCode = trimmed;
            this.announce(formatMessage(messages.addedAnnouncement, { code: trimmed, count: this.items.length }));

            emitScanFeedback(qrFeedback, {
                sound,
                vibrate,
                beepFrequency,
                beepDurationMs,
                vibrateDurationMs,
            });

            // Single server-notify channel: prefer the Livewire hook when the
            // host defines it, otherwise fall back to the window event. The
            // server reports the hook (`wireScanHandler`): Livewire's $wire is a
            // proxy that answers every name, so `typeof` cannot detect it.
            // Never both — emitting both double-handles every scan on hosts
            // that wired both channels.
            if (wireScanHandler && this.$wire) {
                this.$wire.handleCollectorScan(trimmed);
            } else {
                window.dispatchEvent(new CustomEvent('qr-collector-item-added', {
                    detail: { code: trimmed },
                }));
            }

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
