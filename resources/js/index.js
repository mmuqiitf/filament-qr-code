import qrScannerComponent from './qr-scanner.js';
import qrScanSequenceComponent from './qr-sequence.js';
import qrCollectorComponent from './qr-collector.js';
import { createWedgeHandler } from './qr-wedge.js';
import { qrFeedback } from './audio-feedback.js';

export {
    qrScannerComponent,
    qrScanSequenceComponent,
    qrCollectorComponent,
    createWedgeHandler,
    qrFeedback,
};

// Global registration for Alpine.js
if (typeof window !== 'undefined') {
    window.FilamentQrCode = {
        qrScannerComponent,
        qrScanSequenceComponent,
        qrCollectorComponent,
        createWedgeHandler,
        qrFeedback,
    };

    const registerComponents = () => {
        if (window.Alpine) {
            window.Alpine.data('qrScanner', qrScannerComponent);
            window.Alpine.data('qrScanSequence', qrScanSequenceComponent);
            window.Alpine.data('qrCollector', qrCollectorComponent);
        }
    };

    if (window.Alpine) {
        registerComponents();
    } else {
        document.addEventListener('alpine:init', registerComponents);
    }
}
