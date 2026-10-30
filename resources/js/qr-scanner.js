import { qrFeedback } from './audio-feedback.js';
import { sanitizeScannedValue } from './qr-hardware-scanner.js';
import { inputRouter } from './input-router.js';
import { findFieldInput, readWireModelPath } from './field-locator.js';
import { fallbackMessages, messagesFrom } from './messages.js';
import { announcerMixin, formatMessage } from './announcer.js';
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
import { createScanDeduper, normalizeCode, requestScanCheck } from './scan-pipeline.js';
import { FLASH_CLASSES, flashElement, playOutcomeFeedback } from './feedback.js';
import { changedKeys, feedSource, listenForControlEvents, observeOptions, readOptionsAttribute } from './runtime-control.js';

const DEFAULT_OPTIONS = {
    nextField: null,
    sound: true,
    vibrate: true,
    beepFrequency: 880,
    beepDurationMs: 80,
    vibrateDurationMs: 100,
    hardwareScanner: true,
    burstThresholdMs: 50,
    terminators: ['Enter', 'Tab'],
    minBarcodeLength: 2,
    minFastKeys: 3,
    scanTimeoutMs: 150,
    hardwareScope: 'focus',
    preventSubmit: true,
    fps: 25,
    qrbox: 250,
    preferRearCamera: true,
    formats: [],
    ...fallbackMessages,
    serverCheck: false,
    componentKey: null,
    cooldownMs: 0,
    clearOnReject: true,
    successFeedback: null,
    errorFeedback: null,
    mode: 'single',
    continuousDisplay: 'empty',
    pauseOnReject: false,
    keepCameraOpen: false,
    syncOptions: 'morph',
};

const ROUTER_OPTIONS = ['hardwareScanner', 'hardwareScope', 'burstThresholdMs', 'minFastKeys', 'minBarcodeLength', 'terminators', 'scanTimeoutMs', 'preventSubmit'];

function definedOnly(options) {
    return Object.fromEntries(Object.entries(options ?? {}).filter(([, value]) => value !== undefined));
}

/**
 * Alpine component for QrScanner field. Options live in `this.options` so
 * they can change at runtime (setOptions, a re-rendered data-qr-options, or
 * a qr-scanner-set-options event) without re-creating the component.
 */
export default function qrScannerComponent({ state = null, statePath = null, ...options } = {}) {
    const deduper = createScanDeduper();
    let storageKey = storageKeyFor('scanner');

    return {
        ...cameraControlsMixin(),
        ...announcerMixin(),
        // State
        value: state,
        statePath: statePath,
        isModalOpen: false,
        pendingFlash: null,
        isScanning: false,
        isLoading: false,
        hasError: false,
        errorMessage: '',
        devices: [],
        selectedDeviceId: null,
        html5Qrcode: null,
        scannerElementId: '',
        unregisterTarget: null,
        isChecking: false,
        isPaused: false,
        rejectionMessage: '',
        queuedScan: null,
        boundDeliverHandler: null,
        options: { ...DEFAULT_OPTIONS, ...definedOnly(options) },
        pendingOptions: null,
        decoderIsStale: false,
        stopObservingOptions: null,
        stopListeningForControls: null,

        init() {
            // The view keeps x-data constant and renders options on data-qr-options:
            // a changed x-data would make Alpine re-create the component on morph.
            const { statePath: renderedStatePath = null, cameraStorageKey = null, ...rendered } = readOptionsAttribute(this.fieldElement()) ?? {};
            this.options = { ...this.options, ...definedOnly(rendered) };
            this.statePath = this.statePath ?? renderedStatePath;
            storageKey = cameraStorageKey || this.options.cameraStorageKey || storageKey;

            this.scannerElementId = `qr-reader-${this.$id('qr-reader')}`;

            this.registerWithRouter();

            this.stopWatchingVisibility = watchPageVisibility(this, {
                stop: () => this.stopScan(),
                resume: () => this.startScan(),
            });

            this.boundDeliverHandler = (event) => {
                if (!this.options.serverCheck || !this.options.componentKey) {
                    return;
                }

                event.preventDefault();
                this.handleScanResult(event.detail?.value, 'hardware');
            };
            this.fieldElement().addEventListener('qr-scan-deliver', this.boundDeliverHandler);

            this.stopObservingOptions = observeOptions(this.fieldElement(), (rendered) => {
                if (this.options.syncOptions !== 'refresh') {
                    this.setOptions(rendered);
                }
            });

            this.stopListeningForControls = listenForControlEvents({
                'qr-scanner-feed': ({ code, source }) => this.feed(code, source),
                'qr-scanner-pause': () => this.pause(),
                'qr-scanner-resume': () => this.resumeScanning(),
                'qr-scanner-reset': () => this.reset(),
                'qr-scanner-refresh': () => this.refreshOptions(),
                'qr-scanner-set-options': ({ options: changes }) => this.setOptions(changes ?? {}),
            }, () => this.identities());

            this.fieldElement().qrScanner = this.controlApi();
        },

        destroy() {
            this.stopWatchingVisibility?.();
            this.stopWatchingVisibility = null;
            if (this.boundDeliverHandler) {
                this.fieldElement().removeEventListener('qr-scan-deliver', this.boundDeliverHandler);
                this.boundDeliverHandler = null;
            }

            this.stopObservingOptions?.();
            this.stopObservingOptions = null;
            this.stopListeningForControls?.();
            this.stopListeningForControls = null;
            delete this.fieldElement().qrScanner;

            this.unregisterTarget?.();
            this.unregisterTarget = null;
            this.stopScan();
        },

        // The documented per-element API: element.qrScanner.
        controlApi() {
            return {
                feed: (code, source) => this.feed(code, source),
                pause: () => this.pause(),
                resume: () => this.resumeScanning(),
                reset: () => this.reset(),
                setOptions: (changes) => this.setOptions(changes),
                refresh: () => this.refreshOptions(),
                getOptions: () => ({ ...this.options }),
                getState: () => ({
                    value: this.value,
                    isPaused: this.isPaused,
                    isChecking: this.isChecking,
                    isScanning: this.isScanning,
                }),
            };
        },

        identities() {
            return [this.getStatePath(), this.fieldName(), this.options.componentKey].filter(Boolean);
        },

        feed(code, source) {
            return this.handleScanResult(code, feedSource(source));
        },

        pause() {
            if (this.isPaused) {
                return;
            }

            this.isPaused = true;
            this.pauseCamera();
            window.dispatchEvent(new CustomEvent('qr-scan-paused', { detail: { field: this.fieldName() } }));
        },

        reset() {
            const wasPaused = this.isPaused;

            this.value = '';
            this.rejectionMessage = '';
            this.queuedScan = null;
            this.isPaused = false;
            deduper.reset();

            if (wasPaused) {
                window.dispatchEvent(new CustomEvent('qr-scan-resumed', { detail: { field: this.fieldName() } }));

                if (this.isModalOpen) {
                    this.resumeCamera();
                }
            }
        },

        refreshOptions() {
            const rendered = readOptionsAttribute(this.fieldElement());

            if (rendered) {
                this.setOptions(rendered);
            }
        },

        // New options wait for a running check; camera options apply on the next camera start.
        setOptions(changes = {}) {
            this.pendingOptions = { ...(this.pendingOptions ?? {}), ...definedOnly(changes) };

            if (!this.isChecking) {
                this.applyPendingOptions();
            }
        },

        applyPendingOptions() {
            const changes = this.pendingOptions;
            this.pendingOptions = null;

            if (!changes) {
                return;
            }

            const changed = changedKeys(this.options, changes);
            this.options = { ...this.options, ...changes };

            if (changed.includes('formats')) {
                this.markDecoderStale();
            }

            if (changed.some((key) => ROUTER_OPTIONS.includes(key))) {
                this.registerWithRouter();
            }
        },

        markDecoderStale() {
            if (this.isScanning) {
                this.decoderIsStale = true;

                return;
            }

            this.html5Qrcode = null;
            this.decoderIsStale = false;
        },

        registerWithRouter() {
            this.unregisterTarget?.();
            this.unregisterTarget = null;

            if (!this.options.hardwareScanner) {
                return;
            }

            const { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs, preventSubmit, hardwareScope } = this.options;

            this.unregisterTarget = inputRouter().register({
                id: `${this.scannerElementId}-${Math.random().toString(36).slice(2)}`,
                element: this.fieldElement(),
                name: this.fieldElement().getAttribute('data-field-name') || null,
                kind: 'field',
                scope: hardwareScope === 'page' ? 'page' : 'focus',
                detection: { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs, preventSubmit },
                accept: () => !this.isChecking && !this.isPaused,
                hold: (scannedValue) => this.holdScan(scannedValue, 'hardware'),
                isModalOpen: () => this.isModalOpen,
                deliver: (scannedValue) => {
                    this.handleScanResult(scannedValue, 'hardware');
                },
            });
        },

        getStatePath() {
            if (this.statePath) {
                return this.statePath;
            }

            return readWireModelPath(this.fieldElement().querySelector('input')) || readWireModelPath(this.fieldElement()) || '';
        },

        openScannerModal() {
            this.isModalOpen = true;
            this.hasError = false;
            this.errorMessage = '';
            this.$nextTick(() => {
                this.loadCamerasAndStart();
            });
        },

        // In Alpine, $el is the element whose directive called the method (e.g. the hidden file input);
        // $root is the x-data field wrapper.
        fieldElement() {
            return this.$root ?? this.$el;
        },

        closeScannerModal() {
            this.stopScan().then(() => {
                this.isModalOpen = false;

                const outcome = this.pendingFlash;
                this.pendingFlash = null;

                if (outcome) {
                    this.$nextTick(() => flashElement(this.fieldElement(), FLASH_CLASSES[outcome]));
                }
            });
        },

        // The modal overlay hides the field, so flash the panel now and the field once the modal closes.
        flashTargetFor(outcome, settings) {
            if (!this.isModalOpen) {
                return this.fieldElement();
            }

            if (settings?.flash) {
                this.pendingFlash = outcome;
            }

            return this.fieldElement().querySelector('[data-qr-scanner-panel]');
        },

        async loadCamerasAndStart() {
            // Closing the modal while devices load bumps the run: skip the start.
            const run = currentCameraRun(this);
            let decoder;
            try {
                decoder = await resolveDecoderModule(this.options.formats);
            } catch {
                this.hasError = true;
                this.errorMessage = this.options.decoderFailedMessage;
                return;
            }

            await loadCameraDevices(this, {
                Html5QrcodeClass: decoder.Html5Qrcode,
                preferRearCamera: this.options.preferRearCamera,
                storageKey,
                requireDevices: true,
                emptyMessage: this.options.noCamerasMessage,
                deniedMessage: this.options.cameraUnavailableMessage,
                insecureMessage: this.options.insecureMessage,
            });

            if (!this.hasError) {
                await this.startScan(run);
            }
        },

        scannerFormatsConfig() {
            return scannerFormatsConfigFor(this.options.formats);
        },

        currentQrbox() {
            return resolveQrboxFor(this.scannerElementId, this.options.qrbox, this.options.formats);
        },

        async startScan(run = null) {
            if (!this.selectedDeviceId) return;

            if (this.isScanning) {
                await this.stopScan();
            }

            run ??= currentCameraRun(this);

            let decoder;
            try {
                decoder = await resolveDecoderModule(this.options.formats);
            } catch {
                this.hasError = true;
                this.errorMessage = this.options.decoderFailedMessage;
                return;
            }

            if (this.decoderIsStale) {
                this.html5Qrcode = null;
                this.decoderIsStale = false;
            }

            if (!this.html5Qrcode) {
                const decoderFormats = decoder.Html5QrcodeSupportedFormats;
                ensureScannerInstance(
                    this,
                    this.scannerElementId,
                    this.options.formats,
                    decoder.Html5Qrcode,
                    decoderFormats
                );
            }

            const config = {
                fps: this.options.fps,
                qrbox: this.currentQrbox(),
            };

            try {
                if (!(await startCameraFeed(this, () => this.html5Qrcode.start(
                    this.selectedDeviceId,
                    config,
                    (decodedText) => {
                        this.onCameraDecode(decodedText);
                    },
                    () => {
                        // Frame scan error (no QR/barcode detected in frame), silent ignore
                    }
                ), run))) {
                    return;
                }

                refreshCameraControls(this);
                persistCameraId(storageKey, this.selectedDeviceId);
            } catch (err) {
                console.debug('Scanner camera start failed:', err);
                this.hasError = true;
                this.errorMessage = this.options.cameraStartFailedMessage;
            }
        },

        async stopScan() {
            await stopCameraFeed(this, 'camera scanner');

            if (this.decoderIsStale) {
                this.html5Qrcode = null;
                this.decoderIsStale = false;
            }
        },

        async handleScanResult(scannedText, source = 'camera') {
            const code = normalizeCode(sanitizeScannedValue(scannedText), this.options.normalize);

            if (!code || this.isChecking || this.isPaused) {
                return { passes: false, ignored: true };
            }

            const field = this.fieldElement().getAttribute('data-field-name') || null;
            const { serverCheck, componentKey, cooldownMs, mode, scanFailedMessage } = this.options;

            if (deduper.isDuplicate(code, cooldownMs)) {
                window.dispatchEvent(new CustomEvent('qr-scan-duplicate', { detail: { value: code, field, source, mode } }));

                return { passes: false, ignored: true };
            }

            if (!serverCheck || !componentKey || !this.$wire) {
                this.acceptLocally(code, source, field);

                return { passes: true, code, source };
            }

            this.isChecking = true;

            try {
                const result = await requestScanCheck(this.$wire, { componentKey, code, source, scanFailedMessage });

                if (result.passes) {
                    this.applyAccepted(result, field);
                } else {
                    this.applyRejected(result, field);
                }

                return result;
            } finally {
                this.isChecking = false;
                this.applyPendingOptions();
                this.replayQueuedScan();
            }
        },

        // Continuous mode keeps one burst that arrived during a check; any more are dropped.
        holdScan(scannedText, source = 'hardware') {
            const code = sanitizeScannedValue(scannedText);

            if (!this.isContinuous() || this.isPaused || !code) {
                return false;
            }

            if (this.queuedScan) {
                this.dispatchDropped(code, source);

                return true;
            }

            this.queuedScan = { code, source };

            return true;
        },

        replayQueuedScan() {
            const queued = this.queuedScan;
            this.queuedScan = null;

            if (!queued) {
                return;
            }

            if (this.isPaused) {
                this.dispatchDropped(queued.code, queued.source);

                return;
            }

            this.$nextTick(() => this.handleScanResult(queued.code, queued.source));
        },

        dispatchDropped(code, source) {
            window.dispatchEvent(new CustomEvent('qr-scan-dropped', {
                detail: { value: code, field: this.fieldName(), source },
            }));
        },

        fieldName() {
            return this.fieldElement().getAttribute('data-field-name') || null;
        },

        resumeScanning() {
            if (!this.isPaused) {
                return;
            }

            this.isPaused = false;
            this.rejectionMessage = '';
            window.dispatchEvent(new CustomEvent('qr-scan-resumed', { detail: { field: this.fieldName() } }));

            if (this.isModalOpen) {
                this.resumeCamera();
            }
        },

        isContinuous() {
            return this.options.mode === 'continuous';
        },

        async onCameraDecode(decodedText) {
            if (!this.options.serverCheck || !this.options.componentKey) {
                await this.handleScanResult(decodedText, 'camera');
                this.closeScannerModal();

                return;
            }

            if (this.isChecking || this.isPaused) {
                return;
            }

            this.pauseCamera();

            const result = await this.handleScanResult(decodedText, 'camera');

            if (result.ignored) {
                this.resumeCamera();

                return;
            }

            if (result.passes) {
                if (this.isContinuous() && this.options.keepCameraOpen) {
                    this.resumeCamera();

                    return;
                }

                this.closeScannerModal();

                return;
            }

            if (!this.isPaused) {
                this.resumeCamera();
            }
        },

        pauseCamera() {
            try {
                this.html5Qrcode?.pause?.(true);
            } catch (error) {
                console.debug('QR camera pause failed:', error);
            }
        },

        resumeCamera() {
            this.pendingFlash = null;

            try {
                this.html5Qrcode?.resume?.();
            } catch (error) {
                console.debug('QR camera resume failed:', error);
            }
        },

        acceptLocally(code, source, field) {
            this.value = code;

            const path = this.getStatePath();
            if (this.$wire && path) {
                this.$wire.set(path, code);
            }

            this.emitDefaultFeedback();
            this.announceAccepted(code);

            const { nextField, mode } = this.options;
            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: { value: code, field, nextField, source, mode },
            }));
            this.announceListenerDelivery(code, source, field);

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        emitDefaultFeedback() {
            const { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs } = this.options;

            emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });
        },

        applyAccepted({ code, source, recordKey }, field) {
            const { successFeedback, beepFrequency, beepDurationMs, nextField, mode, continuousDisplay } = this.options;

            // The server already wrote the state; this keeps the deferred entangle in step without a request.
            this.value = this.isContinuous() && continuousDisplay === 'empty' ? '' : code;
            this.rejectionMessage = '';

            if (successFeedback) {
                playOutcomeFeedback('success', successFeedback, {
                    element: this.flashTargetFor('success', successFeedback),
                    beepFrequency,
                    beepDurationMs,
                });
            } else {
                this.emitDefaultFeedback();
            }

            this.announceAccepted(code);

            window.dispatchEvent(new CustomEvent('qr-scan-accepted', { detail: { value: code, field, source, recordKey, mode } }));
            window.dispatchEvent(new CustomEvent('qr-scanned', {
                detail: { value: code, field, nextField, source, recordKey, mode },
            }));
            this.announceListenerDelivery(code, source, field);

            if (nextField) {
                this.$nextTick(() => this.advanceFocus(nextField));
            }
        },

        announceAccepted(code) {
            const messages = messagesFrom(this.options);

            this.announce(formatMessage(messages.fieldAddedAnnouncement, { code, field: this.options.fieldLabel ?? '' }));
        },

        announceRejected(message) {
            const messages = messagesFrom(this.options);

            this.announce(formatMessage(messages.rejectedAnnouncement, { reason: message || messages.scanRejectedMessage }));
        },

        // Before the router, a page listener wrote hardware scans into this field and
        // announced them with qr-hardware-scanned. Keep that event for listener pages.
        announceListenerDelivery(code, source, field) {
            if (source !== 'hardware' || !inputRouter().hasTargetOfKind('listener')) {
                return;
            }

            window.dispatchEvent(new CustomEvent('qr-hardware-scanned', { detail: { value: code, field } }));
        },

        applyRejected({ code, source, message }, field) {
            const { clearOnReject, pauseOnReject, errorFeedback, beepFrequency, beepDurationMs, mode } = this.options;

            if (clearOnReject) {
                this.value = '';
            }

            if (this.isContinuous()) {
                this.rejectionMessage = message ?? '';
            }

            if (this.isContinuous() && pauseOnReject) {
                this.isPaused = true;
                window.dispatchEvent(new CustomEvent('qr-scan-paused', { detail: { field } }));
            }

            playOutcomeFeedback('error', errorFeedback, {
                element: this.flashTargetFor('error', errorFeedback),
                beepFrequency,
                beepDurationMs,
            });

            this.announceRejected(message);

            window.dispatchEvent(new CustomEvent('qr-scan-rejected', { detail: { value: code, field, source, message, mode } }));
        },

        advanceFocus(targetFieldName) {
            const targetElement = findFieldInput(targetFieldName);

            if (!targetElement) {
                return;
            }

            targetElement.focus();

            if (typeof targetElement.select === 'function') {
                targetElement.select();
            }
        },

        async scanFile(event) {
            const input = event.target;
            const file = input.files?.[0];

            // Reset so picking the same file again still fires `change`.
            input.value = '';

            if (!file) return;

            this.hasError = false;
            this.errorMessage = '';

            let decoder;
            try {
                decoder = await resolveDecoderModule(this.options.formats);
            } catch {
                this.hasError = true;
                this.errorMessage = this.options.decoderFailedMessage;
                return;
            }

            if (!this.html5Qrcode) {
                ensureScannerInstance(
                    this,
                    this.scannerElementId,
                    this.options.formats,
                    decoder.Html5Qrcode,
                    decoder.Html5QrcodeSupportedFormats
                );
            }

            // html5-qrcode refuses a file scan on an instance that is
            // streaming the camera, so pause the feed for the decode.
            const resumeCamera = this.isScanning;
            await this.stopScan();

            let decodedText;
            try {
                decodedText = await this.html5Qrcode.scanFile(file, false);
            } catch (error) {
                console.debug('QR file scan failed:', error);
                this.hasError = true;
                this.errorMessage = this.options.fileScanFailedMessage;

                if (resumeCamera && this.isModalOpen) {
                    await this.startScan();
                }

                return;
            }

            await this.handleScanResult(decodedText, 'upload');
            this.closeScannerModal();
        },
    };
}
