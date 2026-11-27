// @vitest-environment happy-dom
import { afterEach, describe, expect, it } from 'vitest';
import qrCollectorComponent from './qr-collector.js';
import { inputRouter } from './input-router.js';
import { mountComponent } from './alpine-test-harness.js';

function mountCollector() {
    const host = document.createElement('div');
    document.body.appendChild(host);

    return mountComponent(qrCollectorComponent({
        state: [],
        hardwareScanner: true,
        sound: false,
        vibrate: false,
        delayBetweenScansMs: 0,
        offline: 'block',
        offlineBlockedMessage: 'Scanning is blocked while this page is offline.',
    }), { element: host });
}

afterEach(() => {
    inputRouter().destroy();
    document.body.innerHTML = '';
});

describe('collector offline block', () => {
    it('removes the offline listener when destroyed', () => {
        const collector = mountCollector();
        collector.destroy();
        collector.isOnline = true;

        window.dispatchEvent(new Event('offline'));

        expect(collector.isOnline).toBe(true);
    });

    it('labels a hardware burst as hardware when the scan is blocked', () => {
        const router = inputRouter();
        const original = router.register.bind(router);
        let deliver = null;
        router.register = (target) => {
            deliver = target.deliver;

            return original(target);
        };

        const collector = mountCollector();
        router.register = original;
        collector.isOnline = false;
        collector.offlineMode = 'block';
        collector.offlineBlockedMessage = 'Scanning is blocked while this page is offline.';

        expect(deliver('GUN-1').source).toBe('hardware');
    });
});
