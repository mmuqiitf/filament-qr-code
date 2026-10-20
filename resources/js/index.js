import qrScannerComponent from './qr-scanner.js';
import qrScanSequenceComponent from './qr-sequence.js';
import qrCollectorComponent from './qr-collector.js';
import {
    createHardwareScannerHandler,
    isGlobalHardwareListenerActive,
    qrHardwareScannerListenerComponent,
    sanitizeScannedValue,
} from './qr-hardware-scanner.js';
import { qrFeedback } from './audio-feedback.js';
import * as qrCameraCore from './qr-camera-core.js';
import { createInputRouter, inputRouter } from './input-router.js';
import '../css/qr-code.css';

export {
    qrScannerComponent,
    qrScanSequenceComponent,
    qrCollectorComponent,
    qrHardwareScannerListenerComponent,
    createHardwareScannerHandler,
    isGlobalHardwareListenerActive,
    sanitizeScannedValue,
    qrFeedback,
    qrCameraCore,
    createInputRouter,
    inputRouter,
};

// Global registration for Alpine.js
if (typeof window !== 'undefined') {
    window.FilamentQrCode = {
        qrScannerComponent,
        qrScanSequenceComponent,
        qrCollectorComponent,
        qrHardwareScannerListenerComponent,
        createHardwareScannerHandler,
        isGlobalHardwareListenerActive,
        sanitizeScannedValue,
        qrFeedback,
        qrCameraCore,
        createInputRouter,
        // The shared page-wide wedge router: FilamentQrCode.router.register(target).
        get router() {
            return inputRouter();
        },
    };

    const registerComponents = () => {
        if (window.Alpine) {
            window.Alpine.data('qrScanner', qrScannerComponent);
            window.Alpine.data('qrScanSequence', qrScanSequenceComponent);
            window.Alpine.data('qrCollector', qrCollectorComponent);
            window.Alpine.data('qrHardwareScannerListener', qrHardwareScannerListenerComponent);
        }
    };

    if (window.Alpine) {
        registerComponents();
    } else {
        document.addEventListener('alpine:init', registerComponents);
    }
}
