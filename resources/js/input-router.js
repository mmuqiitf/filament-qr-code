import { createHardwareScannerHandler } from './qr-hardware-scanner.js';

const DEFAULT_DETECTION = {
    burstThresholdMs: 50,
    minFastKeys: 1,
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

/**
 * Page-wide keyboard-wedge router: one keydown listener, one burst detector,
 * and exactly one owner per burst (spec P1b section 4).
 */
export function createInputRouter({ win = window, doc = document } = {}) {
    const targets = new Map();
    let detector = null;
    let listening = false;
    let burstCount = 0;
    let burstOrigin = null;
    let lastKeyAt = 0;

    const containsFocus = (target) => {
        const active = doc.activeElement;

        return Boolean(active) && active !== doc.body && target.element.contains(active);
    };

    const accepts = (target, code) => typeof target.accept !== 'function' || target.accept(code) !== false;

    const focusedTarget = () => [...targets.values()].find(containsFocus) ?? null;

    const insideOtherTarget = (element, owner) => [...targets.values()]
        .some((target) => target !== owner && target.element.contains(element));

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
                if (element && !insideOtherTarget(element, target)) {
                    entries.push({ target, element });
                }
            }
        }

        return entries.sort((first, second) => compareDomOrder(first.element, second.element));
    };

    const rememberOrigin = (event) => {
        const now = Date.now();
        const startsSequence = now - lastKeyAt > detectionFor(focusedTarget() ?? {}).burstThresholdMs;
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
        burstCount += 1;
        restoreOrigin(target, chosen);
        target.deliver(code, { burstId: burstCount, reason, element: chosen });
        win.dispatchEvent(new CustomEvent('qr-scan-routed', {
            detail: { value: code, target: target.name ?? null, kind: target.kind ?? 'field', reason, burstId: burstCount },
        }));

        return true;
    };

    const routeBurst = (code) => {
        const modalOwner = [...targets.values()]
            .find((target) => typeof target.isModalOpen === 'function' && target.isModalOpen() && accepts(target, code));

        if (modalOwner && deliver(modalOwner, code, 'modal')) {
            return;
        }

        const focused = focusedTarget();

        if (focused && accepts(focused, code) && deliver(focused, code, 'focus')) {
            return;
        }

        for (const { target, element } of listeningEntries(code)) {
            if (deliver(target, code, 'first-listening', element)) {
                return;
            }
        }

        const fallback = byDomOrder([...targets.values()].filter((target) => target.fallback === true))
            .find((target) => accepts(target, code));

        if (fallback && deliver(fallback, code, 'fallback')) {
            return;
        }

        win.dispatchEvent(new CustomEvent('qr-scan-unrouted', { detail: { value: code } }));
    };

    const buildDetector = () => {
        const settings = detectionFor(focusedTarget() ?? {});

        detector = createHardwareScannerHandler({
            burstThresholdMs: settings.burstThresholdMs,
            minFastKeys: settings.minFastKeys,
            minBarcodeLength: settings.minBarcodeLength,
            terminators: settings.terminators,
            scanTimeoutMs: settings.scanTimeoutMs,
            preventFormSubmit: settings.preventSubmit,
            isGlobalListener: true,
            onScan: routeBurst,
        });
    };

    const handleKeyDown = (event) => {
        if (!detector) {
            buildDetector();
        }

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
