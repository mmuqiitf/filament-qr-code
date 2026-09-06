import qrScannerComponent from './qr-scanner.js';
import qrScanSequenceComponent from './qr-sequence.js';
import qrCollectorComponent from './qr-collector.js';
import { createWedgeHandler, qrWedgeListenerComponent } from './qr-wedge.js';
import { qrFeedback } from './audio-feedback.js';
import '../css/qr-code.css';

export {
    qrScannerComponent,
    qrScanSequenceComponent,
    qrCollectorComponent,
    qrWedgeListenerComponent,
    createWedgeHandler,
    qrFeedback,
};

// Global registration for Alpine.js
if (typeof window !== 'undefined') {
    window.FilamentQrCode = {
        qrScannerComponent,
        qrScanSequenceComponent,
        qrCollectorComponent,
        qrWedgeListenerComponent,
        createWedgeHandler,
        qrFeedback,
    };

    const registerComponents = () => {
        if (window.Alpine) {
            window.Alpine.data('qrScanner', qrScannerComponent);
            window.Alpine.data('qrScanSequence', qrScanSequenceComponent);
            window.Alpine.data('qrCollector', qrCollectorComponent);
            window.Alpine.data('qrWedgeListener', qrWedgeListenerComponent);
        }
    };

    if (window.Alpine) {
        registerComponents();
    } else {
        document.addEventListener('alpine:init', registerComponents);
    }
}
