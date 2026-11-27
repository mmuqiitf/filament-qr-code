import { qrFeedback } from './audio-feedback.js';
import { sanitizeScannedValue } from './qr-hardware-scanner.js';
import { normalizeCode } from './scan-pipeline.js';
import { inputRouter } from './input-router.js';
import { announcerMixin, formatMessage } from './announcer.js';
import { messagesFrom } from './messages.js';
import { bindOnlineState, offlineBlockResult, rememberRejection } from './online-state.js';
import {
    emitScanFeedback,
    ensureScannerInstance,
    cameraAccessGranted,
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

function normalizeInitialItems(state, quantity) {
    if (!Array.isArray(state)) {
        return [];
    }

    const now = new Date().toLocaleTimeString();

    if (!quantity) {
        return state.map((item) =>
            typeof item === 'object' && item !== null && item.code
                ? item
                : { code: String(item), scanned_at: now }
        );
    }

    const grouped = [];
    const byCode = new Map();

    for (const item of state) {
        const isObj = typeof item === 'object' && item !== null && item.code !== undefined;
        const code = isObj ? String(item.code) : String(item);
        const qty = isObj ? Math.max(1, parseInt(item.qty, 10) || 1) : 1;
        const scannedAt = isObj && item.scanned_at ? item.scanned_at : now;

        if (byCode.has(code)) {
            byCode.get(code).qty += qty;
        } else {
            const entry = { code, qty, scanned_at: scannedAt };
            byCode.set(code, entry);
            grouped.push(entry);
        }
    }

    return grouped;
}

/**
 * Alpine component for QrCollector (batch scanning).
 */
export default function qrCollectorComponent({
    state = null,
    statePath = null,
    allowDuplicates = false,
    quantity = false,
    maxItems = null,
    csvImport = false,
    csvExport = false,
    csvFileName = 'collected-codes.csv',
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
    scanRegion = null,
    preferRearCamera = true,
    formats = [],
    cameraControls = true,
    delayBetweenScansMs = 1200,
    cameraStorageKey = null,
    autoStart = false,
    wireScanHandler = false,
    hasItemRules = false,
    normalize = null,
    announcements = true,
    customAnnouncements = false,
    componentKey = null,
    offline = 'banner',
    offlineBlockedMessage = null,
    ...messageOptions
} = {}) {
    const messages = messagesFrom(messageOptions);
    const announceOptions = { announcements, customAnnouncements, componentKey };
    const initialItems = normalizeInitialItems(state, quantity);

    const storageKey = cameraStorageKey || storageKeyFor('collector');

    return {
        ...cameraControlsMixin(),
        ...announcerMixin(),
        state,
        items: initialItems,
        lastAddedCode: null,
        statePath: statePath,
        scannedSet: new Set(initialItems.map(i => i.code)),
        importReport: null,
        isScanning: false,
        isProcessing: false,
        isLoading: false,
        hasError: false,
        errorMessage: '',
        isOnline: true,
        offlineMode: offline,
        offlineBlockedMessage: offlineBlockedMessage || messages.offlineBlockedMessage,
        rejectionReasons: [],
        devices: [],
        selectedDeviceId: null,
        html5Qrcode: null,
        elementId: '',
        unregisterTarget: null,

        init() {
            bindOnlineState(this);
            if (this.items.length === 0 && Array.isArray(this.state) && this.state.length > 0) {
                this.items = normalizeInitialItems(this.state, quantity);
                this.scannedSet = new Set(this.items.map(i => i.code));
            }

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

            // Listing devices asks for the camera: wait for Start unless auto-start
            // is on or access is already granted.
            if (autoStart) {
                this.loadCameras();
            } else {
                cameraAccessGranted().then((granted) => {
                    if (granted) this.loadCameras({ start: false });
                });
            }
        },

        destroy() {
            this.stopWatchingVisibility?.();
            this.stopWatchingVisibility = null;
            this.unregisterTarget?.();
            this.unregisterTarget = null;
            this.stopCollector();
        },

        serializedState() {
            if (quantity) {
                return this.items.map(i => ({
                    code: i.code,
                    qty: Math.max(1, parseInt(i.qty, 10) || 1),
                }));
            }

            return this.items.map(i => i.code);
        },

        syncState() {
            const payload = this.serializedState();
            if (this.statePath && this.$wire) {
                this.$wire.set(this.statePath, payload);
            }
        },

        totalQuantity() {
            if (!quantity) {
                return this.items.length;
            }

            return this.items.reduce((sum, item) => sum + Math.max(1, parseInt(item.qty, 10) || 1), 0);
        },

        maxItemsError() {
            return formatMessage(messages.maxItemsMessage, { max: maxItems });
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
            return resolveQrboxFor(this.elementId, qrbox, formats, scanRegion);
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
            const trimmed = normalizeCode(sanitizeScannedValue(code), normalize);
            if (!trimmed || this.isProcessing) return;

            const blocked = offlineBlockResult(this, trimmed, 'camera');
            if (blocked) {
                this.rejectCode(trimmed, blocked.message);

                return;
            }

            if (!quantity && !allowDuplicates && this.scannedSet.has(trimmed)) {
                // The code just added is usually still in view: not a new rejection.
                if (trimmed !== this.lastAddedCode) {
                    this.rejectCode(trimmed, messages.duplicateMessage);
                }

                return;
            }

            if (hasItemRules) {
                return this.acceptWhenItemRulesPass(trimmed);
            }

            const existingItem = quantity ? this.items.find(i => i.code === trimmed) : null;
            if (maxItems !== null && !existingItem && this.items.length >= maxItems) {
                this.rejectCode(trimmed, this.maxItemsError());

                return;
            }

            this.acceptCode(trimmed);
        },

        acceptCode(trimmed) {
            this.isProcessing = true;
            const existing = quantity ? this.items.find(i => i.code === trimmed) : null;

            if (existing) {
                existing.qty = Math.max(1, parseInt(existing.qty, 10) || 1) + 1;
                existing.scanned_at = new Date().toLocaleTimeString();
            } else {
                this.scannedSet.add(trimmed);
                const entry = {
                    code: trimmed,
                    scanned_at: new Date().toLocaleTimeString(),
                };
                if (quantity) {
                    entry.qty = 1;
                }
                this.items.unshift(entry);
            }

            this.lastAddedCode = trimmed;
            const countForAnnouncement = quantity ? this.totalQuantity() : this.items.length;
            this.announceScan(announceOptions, {
                code: trimmed,
                accepted: true,
                fallback: formatMessage(messages.addedAnnouncement, { code: trimmed, count: countForAnnouncement }),
            });

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
                const detail = quantity
                    ? { code: trimmed, qty: existing ? existing.qty : 1 }
                    : { code: trimmed };
                window.dispatchEvent(new CustomEvent('qr-collector-item-added', {
                    detail,
                }));
            }

            this.syncState();

            if (delayBetweenScansMs > 0) {
                setTimeout(() => {
                    this.isProcessing = false;
                }, delayBetweenScansMs);
            } else {
                this.isProcessing = false;
            }
        },

        // Item rules are checked before the code is added. A missing key or a
        // failed call rejects the code; it is not accepted unchecked.
        async acceptWhenItemRulesPass(trimmed) {
            this.isProcessing = true;

            if (!componentKey || typeof this.$wire?.callSchemaComponentMethod !== 'function') {
                this.releaseRejectedCode(trimmed, messages.scanFailedMessage);

                return;
            }

            let result;

            try {
                result = await this.$wire.callSchemaComponentMethod(componentKey, 'checkCollectorItem', { code: trimmed });
            } catch {
                this.releaseRejectedCode(trimmed, messages.scanFailedMessage);

                return;
            }

            if (!result || result.passes !== true) {
                const reason = typeof result?.message === 'string' && result.message !== ''
                    ? result.message
                    : messages.scanFailedMessage;

                this.releaseRejectedCode(trimmed, reason);

                return;
            }

            const stored = typeof result.code === 'string' && result.code !== '' ? result.code : trimmed;
            const existingItem = quantity ? this.items.find(i => i.code === stored) : null;

            if (!quantity && !allowDuplicates && this.scannedSet.has(stored)) {
                this.releaseRejectedCode(stored, messages.duplicateMessage);

                return;
            }

            if (maxItems !== null && !existingItem && this.items.length >= maxItems) {
                this.releaseRejectedCode(stored, this.maxItemsError());

                return;
            }

            this.acceptCode(stored);
        },

        releaseRejectedCode(trimmed, reason) {
            this.rejectCode(trimmed, reason);

            if (delayBetweenScansMs > 0) {
                setTimeout(() => {
                    this.isProcessing = false;
                }, delayBetweenScansMs);
            } else {
                this.isProcessing = false;
            }
        },

        rejectCode(trimmed, reason) {
            this.rejectionReasons = rememberRejection(this.rejectionReasons, reason);
            this.announceScan(announceOptions, {
                code: trimmed,
                accepted: false,
                reason,
                fallback: formatMessage(messages.rejectedAnnouncement, { reason }),
            });
        },

        incrementQty(index) {
            const item = this.items[index];
            if (!item) return;

            item.qty = Math.max(1, parseInt(item.qty, 10) || 1) + 1;
            this.syncState();
        },

        decrementQty(index) {
            const item = this.items[index];
            if (!item) return;

            item.qty = Math.max(1, (parseInt(item.qty, 10) || 1) - 1);
            this.syncState();
        },

        setQty(index, rawValue) {
            const item = this.items[index];
            if (!item) return;

            const parsed = parseInt(rawValue, 10);
            item.qty = Number.isFinite(parsed) && parsed >= 1 ? parsed : 1;
            this.syncState();
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

        escapeCsvCell(value) {
            const str = String(value ?? '');
            if (/[",\r\n]/.test(str)) {
                return `"${str.replace(/"/g, '""')}"`;
            }

            return str;
        },

        buildCsv() {
            const lines = [quantity ? 'code,qty' : 'code'];

            for (const item of this.items) {
                if (quantity) {
                    const qty = Math.max(1, parseInt(item.qty, 10) || 1);
                    lines.push(`${this.escapeCsvCell(item.code)},${qty}`);
                } else {
                    lines.push(this.escapeCsvCell(item.code));
                }
            }

            return `${lines.join('\n')}\n`;
        },

        exportCsv() {
            if (!csvExport) return;

            const csv = this.buildCsv();
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = csvFileName || 'collected-codes.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        async importCsvFile(event) {
            const file = event?.target?.files?.[0];
            if (!file) return;

            try {
                const text = await file.text();
                await this.importCsvText(text);
            } finally {
                if (event?.target) {
                    event.target.value = '';
                }
            }
        },

        async importCsvText(csv) {
            if (!csvImport || !componentKey || typeof this.$wire?.callSchemaComponentMethod !== 'function') {
                this.rejectCode('', messages.scanFailedMessage);

                return;
            }

            const existing = this.serializedState();
            let result;

            try {
                result = await this.$wire.callSchemaComponentMethod(componentKey, 'importCollectorCsv', { csv, existing });
            } catch {
                this.rejectCode('', messages.scanFailedMessage);

                return;
            }

            if (!result || !Array.isArray(result.items)) {
                this.rejectCode('', messages.scanFailedMessage);

                return;
            }

            const existingTimestamps = new Map(this.items.map(i => [i.code, i.scanned_at]));
            const now = new Date().toLocaleTimeString();

            this.items = result.items.map((item) => {
                if (quantity && typeof item === 'object' && item !== null) {
                    const code = String(item.code);

                    return {
                        code,
                        qty: Math.max(1, parseInt(item.qty, 10) || 1),
                        scanned_at: existingTimestamps.get(code) ?? now,
                    };
                }

                const code = String(item);

                return {
                    code,
                    scanned_at: existingTimestamps.get(code) ?? now,
                };
            });

            this.scannedSet = new Set(this.items.map(i => i.code));
            this.importReport = {
                added: Number(result.added) || 0,
                rejected: Array.isArray(result.rejected) ? result.rejected : [],
            };
            this.syncState();
        },

        importSummary() {
            if (!this.importReport) return '';

            const rejectedCount = Array.isArray(this.importReport.rejected) ? this.importReport.rejected.length : 0;

            if (rejectedCount > 0) {
                return formatMessage(messages.csvImportSummary, {
                    added: this.importReport.added,
                    rejected: rejectedCount,
                });
            }

            return formatMessage(messages.csvImportSuccess, {
                added: this.importReport.added,
            });
        },

        formatRejectedRow(entry) {
            return formatMessage(messages.csvRejectedRow, {
                row: entry.row,
                code: entry.code || '-',
                reason: entry.reason,
            });
        },
    };
}
