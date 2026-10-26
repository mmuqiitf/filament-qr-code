import { qrFeedback } from './audio-feedback.js';
import { sanitizeScannedValue } from './qr-hardware-scanner.js';
import { normalizeCode } from './scan-pipeline.js';
import { inputRouter } from './input-router.js';
import { announcerMixin, formatMessage } from './announcer.js';
import { fallbackMessages } from './messages.js';
import { changedKeys, feedSource, listenForControlEvents, observeOptions, readOptionsAttribute } from './runtime-control.js';
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

const DEFAULT_OPTIONS = {
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
    fps: 25,
    qrbox: 250,
    preferRearCamera: true,
    formats: [],
    statePrefix: 'data',
    componentStatePath: null,
    serverCheck: false,
    steps: [],
    componentKey: null,
    ...fallbackMessages,
    syncOptions: 'morph',
};

const ROUTER_OPTIONS = ['hardwareScanner', 'burstThresholdMs', 'minFastKeys', 'minBarcodeLength', 'terminators', 'scanTimeoutMs'];

function definedOnly(options) {
    return Object.fromEntries(Object.entries(options ?? {}).filter(([, value]) => value !== undefined));
}

// PHP sends an empty array for "no values".
function normalizeValues(values) {
    return values && typeof values === 'object' && !Array.isArray(values) ? { ...values } : {};
}

/**
 * Alpine component for QrScanSequence container. Options live in
 * `this.options` and follow the server through data-qr-options on the
 * wrapper outside the wire:ignore'd UI.
 */
export default function qrScanSequenceComponent({ fields = [], editable = true, values = undefined, cameraStorageKey = null, ...options } = {}) {
    const storageKey = cameraStorageKey || storageKeyFor('sequence');

    return {
        ...cameraControlsMixin(),
        ...announcerMixin(),
        fields: fields, // array of { key: string, label: string }
        currentFieldIndex: 0,
        results: {},
        editable: editable,
        renderedEditable: editable,
        isScanning: false,
        isLoading: false,
        hasError: false,
        errorMessage: '',
        devices: [],
        selectedDeviceId: null,
        html5Qrcode: null,
        elementId: '',
        unregisterTarget: null,
        queuedScan: null,
        isChecking: false,
        isPaused: false,
        stepError: null,
        options: { ...DEFAULT_OPTIONS, ...definedOnly(options) },
        pendingOptions: null,
        deferredFields: null,
        decoderIsStale: false,
        stopObservingOptions: null,
        stopListeningForControls: null,

        init() {
            this.elementId = `qr-sequence-${this.$id('qr-seq')}`;

            if (this.options.serverCheck && values !== undefined) {
                this.results = normalizeValues(values);
                this.currentFieldIndex = this.firstEmptyActiveIndex();
            }

            this.registerWithRouter();

            this.stopWatchingVisibility = watchPageVisibility(this, {
                stop: () => this.stopScanner(),
                resume: () => this.startScanner(),
            });

            this.stopObservingOptions = observeOptions(this.$root ?? this.$el, (rendered) => {
                if (this.options.syncOptions !== 'refresh') {
                    this.setOptions(rendered);
                }
            });

            this.stopListeningForControls = listenForControlEvents({
                'qr-sequence-feed': ({ code, source }) => this.feed(code, source),
                'qr-sequence-go-to': ({ step }) => this.goToStep(step),
                'qr-sequence-reset': ({ values: seeded, fromServer }) => this.resetTo(seeded, { writeState: !fromServer }),
                'qr-sequence-pause': () => this.pause(),
                'qr-sequence-resume': () => this.resume(),
                'qr-sequence-refresh': () => this.refreshOptions(),
                'qr-sequence-set-options': ({ options: changes }) => this.setOptions(changes ?? {}),
                'qr-sequence-set-repeat': ({ step, count }) => this.setRepeatCount(step, count),
            }, () => [this.options.componentKey, this.options.componentStatePath].filter(Boolean));

            (this.$root ?? this.$el).qrScanSequence = this.controlApi();

            this.loadCameras();
        },

        destroy() {
            this.stopWatchingVisibility?.();
            this.stopWatchingVisibility = null;
            this.stopObservingOptions?.();
            this.stopObservingOptions = null;
            this.stopListeningForControls?.();
            this.stopListeningForControls = null;
            delete (this.$root ?? this.$el).qrScanSequence;

            this.unregisterTarget?.();
            this.unregisterTarget = null;
            this.stopScanner();
        },

        // The documented per-element API: element.qrScanSequence.
        controlApi() {
            return {
                feed: (code, source) => this.feed(code, source),
                goToStep: (step) => this.goToStep(step),
                reset: ({ values: seeded = {} } = {}) => this.resetTo(seeded),
                pause: () => this.pause(),
                resume: () => this.resume(),
                setOptions: (changes) => this.setOptions(changes),
                refresh: () => this.refreshOptions(),
                getOptions: () => ({ ...this.options, fields: [...this.fields], editable: this.editable }),
                getState: () => ({
                    currentStep: this.getCurrentField()?.key ?? null,
                    values: { ...this.results },
                    isPaused: this.isPaused,
                    isChecking: this.isChecking,
                    isScanning: this.isScanning,
                }),
            };
        },

        feed(code, source) {
            if (this.isPaused) return undefined;

            return this.processScan(code, feedSource(source));
        },

        goToStep(key) {
            const index = this.indexOfStep(key);

            if (index < 0) return;

            this.currentFieldIndex = index;
            this.stepError = null;
        },

        pause() {
            this.isPaused = true;
        },

        resume() {
            this.isPaused = false;
        },

        /**
         * Start over with seeded values. Page code also writes them to Livewire
         * state; a reset the server sent already did that.
         */
        resetTo(seeded = {}, { writeState = true } = {}) {
            this.results = normalizeValues(seeded);
            this.stepError = null;
            this.isPaused = false;
            this.queuedScan = null;
            this.applyDeferredFields();
            this.currentFieldIndex = this.firstEmptyActiveIndex();

            if (writeState) this.writeAllValues();
        },

        writeAllValues() {
            if (!this.$wire) return;

            this.fields.forEach(({ key }) => this.$wire.set(this.statePathFor(key), this.results[key] ?? null, false));

            if (this.options.componentStatePath) {
                this.$wire.set(this.options.componentStatePath, { ...this.results });
            }
        },

        setRepeatCount(key, count) {
            this.options = {
                ...this.options,
                steps: this.options.steps.map((step) => (step.key === key ? { ...step, repeat: count ?? null } : step)),
            };
        },

        refreshOptions() {
            const rendered = readOptionsAttribute(this.$root ?? this.$el);

            if (rendered) this.setOptions(rendered);
        },

        // Options wait for a running check; the step list waits for the round to end.
        setOptions(changes = {}) {
            this.pendingOptions = { ...(this.pendingOptions ?? {}), ...definedOnly(changes) };

            if (!this.isChecking) this.applyPendingOptions();
        },

        applyPendingOptions() {
            const changes = this.pendingOptions;
            this.pendingOptions = null;

            if (!changes) return;

            const { fields: nextFields, values: nextValues, editable: nextEditable, ...rest } = changes;
            const changed = changedKeys(this.options, rest);
            this.options = { ...this.options, ...rest };

            if (changed.includes('formats')) this.markDecoderStale();
            if (changed.some((key) => ROUTER_OPTIONS.includes(key))) this.registerWithRouter();

            // Only a server-side change of editable() wins over a qr-sequence-editable toggle.
            if (nextEditable !== undefined && nextEditable !== this.renderedEditable) {
                this.renderedEditable = nextEditable;
                this.editable = nextEditable;
            }

            if (nextFields !== undefined) this.applyFields(nextFields);
            if (nextValues !== undefined && this.options.serverCheck) this.applyValues(nextValues);
        },

        applyFields(nextFields) {
            const keysOf = (list) => list.map(({ key }) => key).join('\u0000');

            if (keysOf(nextFields) === keysOf(this.fields)) {
                this.fields = nextFields;

                return;
            }

            if (this.isRoundInProgress()) {
                this.deferredFields = nextFields;

                return;
            }

            this.fields = nextFields;
            this.deferredFields = null;
            this.currentFieldIndex = this.firstEmptyActiveIndex();
        },

        applyDeferredFields() {
            if (!this.deferredFields) return;

            this.fields = this.deferredFields;
            this.deferredFields = null;
        },

        // A round is in progress once any non-sticky step holds a value.
        isRoundInProgress() {
            return Object.keys(this.results).some((key) => !this.isSticky(key));
        },

        applyValues(nextValues) {
            const normalized = normalizeValues(nextValues);

            if (JSON.stringify(normalized) === JSON.stringify(this.results)) return;

            this.results = normalized;

            const current = this.getCurrentField();

            if (!current || !this.isActive(current.key) || this.isFilled(current.key)) {
                this.currentFieldIndex = this.firstEmptyActiveIndex();
            }
        },

        isFilled(key) {
            const value = this.results[key];

            if (!this.isRepeating(key)) return value !== undefined && value !== null && value !== '';

            const count = Array.isArray(value) ? value.length : 0;

            return count >= (this.stepOptions(key)?.repeat ?? 1);
        },

        isActive(key) {
            return this.stepOptions(key)?.active !== false;
        },

        firstEmptyActiveIndex() {
            const index = this.fields.findIndex(({ key }) => this.isActive(key) && !this.isFilled(key));

            return index >= 0 ? index : 0;
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

            if (!this.options.hardwareScanner) return;

            const { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs } = this.options;

            this.unregisterTarget = inputRouter().register({
                id: `sequence-${this.elementId}-${Math.random().toString(36).slice(2)}`,
                element: this.$root ?? this.$el,
                name: null,
                kind: 'sequence',
                scope: 'page',
                detection: { burstThresholdMs, minFastKeys, minBarcodeLength, terminators, scanTimeoutMs },
                // While a server check runs, refuse bursts so the router can hold one for us.
                accept: () => !this.isChecking && !this.isPaused,
                hold: (scannedValue) => this.holdScan(scannedValue),
                deliver: (scannedValue) => {
                    this.focusStepFromInput();
                    this.processScan(scannedValue, 'hardware');
                },
            });
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
                deniedMessage: this.options.cameraUnavailableMessage,
                emptyMessage: this.options.noCamerasMessage,
                insecureMessage: this.options.insecureMessage,
                fixedMessage: true,
            });
        },

        scannerFormatsConfig() {
            return scannerFormatsConfigFor(this.options.formats);
        },

        currentQrbox() {
            return resolveQrboxFor(this.elementId, this.options.qrbox, this.options.formats);
        },

        statePathFor(fieldKey) {
            const prefix = (this.options.statePrefix || '').trim().replace(/\.$/, '');
            return prefix ? `${prefix}.${fieldKey}` : fieldKey;
        },

        // Retry button of the shared camera-status overlay.
        async retryCamera() {
            this.hasError = false;
            this.errorMessage = '';
            await this.loadCameras();

            if (this.selectedDeviceId && !this.hasError) {
                await this.startScanner();
            }
        },

        async startScanner() {
            // Start pressed before the permission prompt resolved: load the
            // cameras now instead of silently doing nothing.
            if (!this.selectedDeviceId) {
                await this.loadCameras();
            }

            if (!this.selectedDeviceId) {
                if (!this.hasError) {
                    this.hasError = true;
                    this.errorMessage = this.options.cameraUnavailableMessage;
                }

                return;
            }

            if (this.isScanning) {
                await this.stopScanner();
            }

            const run = currentCameraRun(this);

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
                ensureScannerInstance(this, this.elementId, this.options.formats, decoder.Html5Qrcode, decoderFormats);
            }

            try {
                if (!(await startCameraFeed(this, () => this.html5Qrcode.start(
                    this.selectedDeviceId,
                    { fps: this.options.fps, qrbox: this.currentQrbox() },
                    (decodedText) => {
                        this.processScan(decodedText, 'camera');
                    },
                    () => {}
                ), run))) {
                    return;
                }
                refreshCameraControls(this);
                persistCameraId(storageKey, this.selectedDeviceId);
            } catch (err) {
                console.debug('Sequence camera start failed:', err);
                this.hasError = true;
                this.errorMessage = this.options.cameraStartFailedMessage;
            }
        },

        async stopScanner() {
            await stopCameraFeed(this, 'sequence scanner');

            if (this.decoderIsStale) {
                this.html5Qrcode = null;
                this.decoderIsStale = false;
            }
        },

        processScan(decodedText, source = 'camera') {
            const currentField = this.getCurrentField();
            if (!currentField) return;

            const trimmed = normalizeCode(sanitizeScannedValue(decodedText), this.options.normalize);
            if (!trimmed || this.isPaused) return;

            const { serverCheck, componentKey, componentStatePath } = this.options;

            if (serverCheck && componentKey && this.$wire) {
                return this.processServerScan(currentField, trimmed, source)
                    .finally(() => this.replayQueuedScan());
            }

            this.results[currentField.key] = trimmed;
            this.emitFeedback();

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
                    source,
                },
            }));

            // Auto-advance to the next active field, or stop the feed when
            // complete so the button never gets stuck on "Stop".
            const nextIndex = this.fields.findIndex((field, index) => index > this.currentFieldIndex && this.isActive(field.key));
            this.announceAccepted(currentField.key, trimmed, nextIndex < 0);

            if (nextIndex >= 0) {
                this.currentFieldIndex = nextIndex;
            } else {
                this.stopScanner();
                window.dispatchEvent(new CustomEvent('qr-sequence-completed', {
                    detail: { results: { ...this.results } },
                }));
            }
        },

        // A focused step input owns the burst: scan into that step.
        focusStepFromInput() {
            const stepKey = document.activeElement?.closest?.('[data-qr-step]')?.getAttribute('data-qr-step');
            const index = stepKey ? this.indexOfStep(stepKey) : -1;

            if (index >= 0) this.currentFieldIndex = index;
        },

        // One burst that arrives during a server check waits; any more are dropped.
        holdScan(scannedValue) {
            const code = sanitizeScannedValue(scannedValue);

            if (!code) return false;

            if (this.queuedScan) {
                window.dispatchEvent(new CustomEvent('qr-scan-dropped', {
                    detail: { value: code, field: this.getCurrentField()?.key ?? null, source: 'hardware' },
                }));

                return true;
            }

            this.queuedScan = code;

            return true;
        },

        replayQueuedScan() {
            const queued = this.queuedScan;
            this.queuedScan = null;

            if (queued) return this.processScan(queued, 'hardware');
        },

        emitFeedback() {
            // The single beep for this delivery (camera or hardware): the
            // interceptor that routed hardware bursts never beeps.
            const { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs } = this.options;

            emitScanFeedback(qrFeedback, { sound, vibrate, beepFrequency, beepDurationMs, vibrateDurationMs });
        },

        // One polite announcement per accepted scan; completion rides along so it is not overwritten.
        announceAccepted(stepKey, code, completed) {
            const parts = [];
            const label = this.fields[this.indexOfStep(stepKey)]?.label || stepKey;

            if (stepKey && code) parts.push(formatMessage(this.options.stepAddedAnnouncement, { code, step: label }));
            if (completed) parts.push(this.options.completedAnnouncement);

            this.announce(parts.join('. '));
        },

        indexOfStep(key) {
            return this.fields.findIndex((field) => field.key === key);
        },

        /**
         * One handleStepScan call per scan: the server checks the step, writes
         * state and answers with the step to show next. Scans that arrive
         * while a check is in flight are dropped.
         */
        stepOptions(key) {
            return this.options.steps.find((step) => step.key === key) ?? null;
        },

        isRepeating(key) {
            return Boolean(this.stepOptions(key)?.repeating);
        },

        // "k / n" for a counted repeat step, "k" when open-ended, null otherwise.
        stepProgress(key) {
            const options = this.stepOptions(key);

            if (!options?.repeating) return null;

            const count = Array.isArray(this.results[key]) ? this.results[key].length : 0;

            return options.repeat ? `${count} / ${options.repeat}` : `${count}`;
        },

        async finishStep(key) {
            const field = this.fields[this.indexOfStep(key)];

            if (!field || !this.canReachServer()) return;

            return this.processServerScan(field, '', 'manual', { method: 'finishStep', args: { step: key } })
                .finally(() => this.replayQueuedScan());
        },

        isSticky(key) {
            return Boolean(this.stepOptions(key)?.sticky);
        },

        // Remove one mis-scanned code from a repeat step.
        async removeCode(key, index) {
            return this.runStepAction(key, 'removeStepCode', { step: key, index });
        },

        // The Clear control: empty a step (usually sticky) and ask for it again.
        async clearStep(key) {
            return this.runStepAction(key, 'clearStep', { step: key });
        },

        async runStepAction(key, method, args) {
            const field = this.fields[this.indexOfStep(key)];

            if (!field || !this.canReachServer()) return;

            return this.processServerScan(field, '', 'manual', { method, args, quiet: true })
                .finally(() => this.replayQueuedScan());
        },

        async processServerScan(currentField, code, source, { keepStep = false, method = 'handleStepScan', args = null, quiet = false } = {}) {
            if (this.isChecking) return;

            const { componentKey, scanFailedMessage } = this.options;
            this.isChecking = true;
            let result;

            try {
                result = await this.$wire.callSchemaComponentMethod(
                    componentKey,
                    method,
                    args ?? { step: currentField.key, code, source },
                );
            } catch (error) {
                console.error('QR sequence check failed:', error);
                result = null;
            } finally {
                this.isChecking = false;
            }

            const applyQueuedOptions = () => this.applyPendingOptions();

            if (result === null || typeof result !== 'object' || typeof result.passes !== 'boolean') {
                result = { passes: false, step: currentField.key, next_step: currentField.key, message: scanFailedMessage, values: { ...this.results } };
            }

            const completedValues = { ...this.results };
            (result.skipped || []).forEach((key) => delete completedValues[key]);
            if (result.passes && result.step && !result.finished) {
                const value = result.code ?? code;
                const previous = completedValues[result.step];

                completedValues[result.step] = this.isRepeating(result.step)
                    ? [...(Array.isArray(previous) ? previous : []), value]
                    : value;
            }

            this.results = normalizeValues(result.values);

            if (result.completed || result.reset) this.applyDeferredFields();

            // A rejected typed correction stays visible so the operator can fix it.
            if (!result.passes && keepStep && code !== '') this.results[currentField.key] = code;

            const nextIndex = this.indexOfStep(result.next_step);
            if (nextIndex >= 0 && !keepStep) this.currentFieldIndex = nextIndex;

            applyQueuedOptions();

            if (!result.passes) {
                this.stepError = result.message || scanFailedMessage;
                this.announce(formatMessage(this.options.rejectedAnnouncement, { reason: this.stepError }));

                if (result.invalid_step) {
                    window.dispatchEvent(new CustomEvent('qr-sequence-invalid', {
                        detail: { field: result.invalid_step, message: this.stepError, values: { ...(result.values || {}) } },
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('qr-sequence-rejected', {
                        detail: { field: result.step ?? currentField.key, value: code, message: this.stepError, source },
                    }));
                }

                return result;
            }

            this.stepError = null;

            // Remove and Clear only resync state: no feedback, no step events.
            if (quiet) return result;

            if (source !== 'manual') this.emitFeedback();

            this.announceAccepted(result.finished ? null : result.step, result.code ?? code, Boolean(result.completed));

            if (!result.finished) window.dispatchEvent(new CustomEvent('qr-sequence-step', {
                detail: {
                    field: result.step,
                    value: result.code ?? code,
                    index: this.indexOfStep(result.step),
                    skipped: result.skipped || [],
                    source,
                },
            }));

            if (result.completed) {
                if (!result.reset) this.stopScanner();

                window.dispatchEvent(new CustomEvent('qr-sequence-completed', {
                    detail: { results: completedValues, submitted: result.submitted ?? null },
                }));
            }

            return result;
        },

        /**
         * Sync a manually typed or corrected step value to form state.
         */
        syncEditedValue(fieldKey) {
            const trimmed = ((this.results[fieldKey] || '') + '').trim();

            // Typed corrections go through the same server checks as scans.
            if (trimmed && this.canReachServer()) {
                const field = this.fields[this.indexOfStep(fieldKey)] ?? { key: fieldKey };
                this.results[fieldKey] = trimmed;

                return this.processServerScan(field, trimmed, 'manual', { keepStep: true });
            }

            if (!trimmed) {
                delete this.results[fieldKey];
            } else {
                this.results[fieldKey] = trimmed;
            }

            if (this.$wire) {
                this.$wire.set(this.statePathFor(fieldKey), trimmed);

                if (this.options.componentStatePath) {
                    this.$wire.set(this.options.componentStatePath, { ...this.results });
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
            this.stepError = null;
            this.applyDeferredFields();
        },

        canReachServer() {
            return Boolean(this.options.serverCheck && this.options.componentKey && this.$wire);
        },
    };
}
