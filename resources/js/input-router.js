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

/**
 * Page-wide keyboard-wedge router: one keydown listener, one burst detector,
 * and exactly one owner per burst (spec P1b section 4).
 */
export function createInputRouter({ win = window, doc = document } = {}) {
    const targets = new Map();
    let detector = null;
    let listening = false;

    const containsFocus = (target) => {
        const active = doc.activeElement;

        return Boolean(active) && active !== doc.body && target.element.contains(active);
    };

    const accepts = (target, code) => typeof target.accept !== 'function' || target.accept(code) !== false;

    const focusedTarget = () => [...targets.values()].find(containsFocus) ?? null;

    const deliver = (target, code, reason, element) => {
        if (code.length < detectionFor(target).minBarcodeLength) {
            return false;
        }

        target.deliver(code, { reason, element: element ?? target.element });

        return true;
    };

    const routeBurst = (code) => {
        const focused = focusedTarget();

        if (focused && accepts(focused, code) && deliver(focused, code, 'focus')) {
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
