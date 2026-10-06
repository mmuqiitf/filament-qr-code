import qrScannerComponent from './qr-scanner.js';
import qrScanSequenceComponent from './qr-sequence.js';
import qrCollectorComponent from './qr-collector.js';
import { createHardwareScannerHandler, qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import { qrFeedback } from './audio-feedback.js';
import * as qrCameraCore from './qr-camera-core.js';
import '../css/qr-code.css';

export {
    qrScannerComponent,
    qrScanSequenceComponent,
    qrCollectorComponent,
    qrHardwareScannerListenerComponent,
    createHardwareScannerHandler,
    qrFeedback,
    qrCameraCore,
};

// Global registration for Alpine.js
if (typeof window !== 'undefined') {
    window.FilamentQrCode = {
        qrScannerComponent,
        qrScanSequenceComponent,
        qrCollectorComponent,
        qrHardwareScannerListenerComponent,
        createHardwareScannerHandler,
        qrFeedback,
        qrCameraCore,
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
