import { createHardwareScannerHandler } from './qr-hardware-scanner.js';

const DEFAULT_DETECTION = {
    burstThresholdMs: 50,
    minFastKeys: 3,
    minBarcodeLength: 2,
    terminators: ['Enter', 'Tab'],
    scanTimeoutMs: 150,
    preventSubmit: true,
};

function detectionFor(target) {
    return { ...DEFAULT_DETECTION, ...(target.detection ?? {}) };
}

function compareDomOrder(first, second) {
    if (first === second) {
        return 0;
    }

    return first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
}

function byDomOrder(targets) {
    return [...targets].sort((first, second) => compareDomOrder(first.element, second.element));
}

function mostPermissive(detections) {
    if (detections.length === 0) {
        return { ...DEFAULT_DETECTION };
    }

    return {
        burstThresholdMs: Math.max(...detections.map((settings) => settings.burstThresholdMs)),
        minFastKeys: Math.min(...detections.map((settings) => settings.minFastKeys)),
        minBarcodeLength: Math.min(...detections.map((settings) => settings.minBarcodeLength)),
        terminators: [...new Set(detections.flatMap((settings) => settings.terminators))],
        scanTimeoutMs: Math.max(...detections.map((settings) => settings.scanTimeoutMs)),
        preventSubmit: detections.some((settings) => settings.preventSubmit),
    };
}

/**
 * Page-wide keyboard-wedge router: one keydown listener, one burst detector,
 * and exactly one owner per burst (spec P1b section 4).
 */
export function createInputRouter({ win = window, doc = document } = {}) {
    const targets = new Map();
    let detector = null;
    let detectorSettingsKey = null;
    let listening = false;
    let burstCount = 0;
    let burstOrigin = null;
    let lastKeyAt = 0;

    const containsFocus = (target) => {
        const active = doc.activeElement;

        return Boolean(active) && active !== doc.body && target.element.contains(active);
    };

    const accepts = (target, code) => typeof target.accept !== 'function' || target.accept(code) !== false;

    const focusedEntry = () => {
        const containing = [...targets.values()].find(containsFocus);

        if (containing) {
            return { target: containing, element: containing.element };
        }

        const active = doc.activeElement;

        if (!active || active === doc.body) {
            return null;
        }

        const owner = [...targets.values()]
            .find((target) => typeof target.ownsFocus === 'function' && target.ownsFocus(active));

        return owner ? { target: owner, element: active } : null;
    };

    const focusedTarget = () => focusedEntry()?.target ?? null;

    // A candidate inside a page-scope target belongs to that target; one inside a
    // focus-scope target stays with the listener (its scanner handoff path).
    const insidePageTarget = (element, owner) => [...targets.values()]
        .some((target) => target !== owner && target.scope === 'page' && target.element.contains(element));

    const listeningEntries = (code) => {
        const entries = [];

        for (const target of targets.values()) {
            if (target.scope !== 'page' || !accepts(target, code)) {
                continue;
            }

            if (typeof target.candidates !== 'function') {
                entries.push({ target, element: target.element });

                continue;
            }

            for (const element of target.candidates()) {
                if (element && !insidePageTarget(element, target)) {
                    entries.push({ target, element });
                }
            }
        }

        return entries.sort((first, second) => compareDomOrder(first.element, second.element));
    };

    const rememberOrigin = (event) => {
        const now = Date.now();
        const startsSequence = now - lastKeyAt > currentSettings().burstThresholdMs;
        lastKeyAt = now;

        if (!startsSequence) {
            return;
        }

        const active = event.target instanceof Element ? event.target : doc.activeElement;
        const isTextEntry = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');
        burstOrigin = isTextEntry && !active.closest('[data-qr-ignore]') ? { element: active, value: active.value } : null;
    };

    const restoreOrigin = (target, chosen) => {
        const origin = burstOrigin;
        burstOrigin = null;

        if (!origin || origin.element === chosen || target.element.contains(origin.element)) {
            return;
        }

        if (origin.element.value === origin.value) {
            return;
        }

        origin.element.value = origin.value;
        origin.element.dispatchEvent(new Event('input', { bubbles: true }));
        origin.element.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const deliver = (target, code, reason, element) => {
        if (code.length < detectionFor(target).minBarcodeLength) {
            return false;
        }

        const chosen = element ?? target.element;
        const burstId = burstCount + 1;

        if (target.deliver(code, { burstId, reason, element: chosen }) === false) {
            return false;
        }

        burstCount = burstId;
        restoreOrigin(target, chosen);
        win.dispatchEvent(new CustomEvent('qr-scan-routed', {
            detail: { value: code, target: target.name ?? null, kind: target.kind ?? 'field', reason, burstId },
        }));

        return true;
    };

    // Last resort before unrouted: a busy target (accept() false) may queue the burst.
    const holdBurst = (code) => {
        const focused = focusedTarget();
        const candidates = [
            ...[...targets.values()].filter((target) => typeof target.isModalOpen === 'function' && target.isModalOpen()),
            ...(focused ? [focused] : []),
            ...byDomOrder([...targets.values()].filter((target) => target.scope === 'page')),
        ];

        for (const target of new Set(candidates)) {
            if (typeof target.hold !== 'function' || accepts(target, code)) {
                continue;
            }

            if (code.length < detectionFor(target).minBarcodeLength) {
                continue;
            }

            const burstId = burstCount + 1;

            if (target.hold(code, { burstId, reason: 'queued', element: target.element }) !== true) {
                continue;
            }

            burstCount = burstId;
            restoreOrigin(target, target.element);
            win.dispatchEvent(new CustomEvent('qr-scan-routed', {
                detail: { value: code, target: target.name ?? null, kind: target.kind ?? 'field', reason: 'queued', burstId },
            }));

            return true;
        }

        return false;
    };

    const routeBurst = (code) => {
        const modalOwner = [...targets.values()]
            .find((target) => typeof target.isModalOpen === 'function' && target.isModalOpen() && accepts(target, code));

        if (modalOwner && deliver(modalOwner, code, 'modal')) {
            return;
        }

        const tried = new Set(modalOwner ? [modalOwner] : []);
        const focused = focusedEntry();

        if (focused && accepts(focused.target, code)) {
            if (deliver(focused.target, code, 'focus', focused.element)) {
                return;
            }

            tried.add(focused.element);
        }

        for (const { target, element } of listeningEntries(code)) {
            if (tried.has(target) || tried.has(element)) {
                continue;
            }

            if (deliver(target, code, 'first-listening', element)) {
                return;
            }
        }

        const fallback = byDomOrder([...targets.values()].filter((target) => target.fallback === true))
            .find((target) => accepts(target, code));

        if (fallback && deliver(fallback, code, 'fallback')) {
            return;
        }

        if (holdBurst(code)) {
            return;
        }

        burstOrigin = null;
        win.dispatchEvent(new CustomEvent('qr-scan-unrouted', { detail: { value: code } }));
    };

    const currentSettings = () => {
        const focused = focusedTarget();

        const all = [...targets.values()].map(detectionFor);
        const shortest = Math.min(...all.map((settings) => settings.minBarcodeLength));

        // Keep the shortest minimum so a refused burst can still fall through to another target.
        return focused ? { ...detectionFor(focused), minBarcodeLength: shortest } : mostPermissive(all);
    };

    const ensureDetector = () => {
        if (detector && detector.getBuffer() !== '') {
            return;
        }

        const settings = currentSettings();
        const settingsKey = JSON.stringify(settings);

        if (detector && settingsKey === detectorSettingsKey) {
            return;
        }

        detector?.reset();
        detectorSettingsKey = settingsKey;

        detector = createHardwareScannerHandler({
            burstThresholdMs: settings.burstThresholdMs,
            minFastKeys: settings.minFastKeys,
            minBarcodeLength: settings.minBarcodeLength,
            terminators: settings.terminators,
            scanTimeoutMs: settings.scanTimeoutMs,
            preventFormSubmit: settings.preventSubmit,
            onScan: routeBurst,
        });
    };

    const handleKeyDown = (event) => {
        ensureDetector();
        rememberOrigin(event);
        detector.handleKeyDown(event);
    };

    const startListening = () => {
        if (listening) {
            return;
        }

        win.addEventListener('keydown', handleKeyDown, true);
        listening = true;
    };

    const stopListening = () => {
        if (!listening) {
            return;
        }

        win.removeEventListener('keydown', handleKeyDown, true);
        listening = false;
        detector?.reset();
        detector = null;
        detectorSettingsKey = null;
    };

    return {
        register(target) {
            targets.set(target.id, target);
            startListening();

            return () => {
                if (targets.get(target.id) === target) {
                    targets.delete(target.id);
                }

                if (targets.size === 0) {
                    stopListening();
                }
            };
        },

        hasTargetOfKind(kind) {
            return [...targets.values()].some((target) => target.kind === kind);
        },

        isActive() {
            return targets.size > 0;
        },

        targetCount() {
            return targets.size;
        },

        destroy() {
            targets.clear();
            stopListening();
        },
    };
}

const ROUTER_KEY = '__filamentQrCodeInputRouter';

/**
 * The shared router for this page. Stored on window so two bundles on one
 * page still share a single owner per burst.
 */
export function inputRouter() {
    if (!window[ROUTER_KEY]) {
        window[ROUTER_KEY] = createInputRouter();
    }

    return window[ROUTER_KEY];
}
