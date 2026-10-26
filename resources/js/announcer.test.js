// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { announcerMixin, formatMessage } from './announcer.js';
import { fallbackMessages } from './messages.js';
import { inputRouter } from './input-router.js';
import qrCollectorComponent from './qr-collector.js';
import qrScanSequenceComponent from './qr-sequence.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

vi.mock('./qr-camera-core.js', async (importOriginal) => ({
    ...(await importOriginal()),
    resolveDecoderModule: () => Promise.reject(new Error('no decoder in tests')),
}));

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
    inputRouter().destroy();
    document.body.innerHTML = '';
});

describe('formatMessage', () => {
    it('replaces :placeholders, longest name first', () => {
        expect(formatMessage('Added :code (:count items)', { code: 'A-1', count: 2 })).toBe('Added A-1 (2 items)');
        expect(formatMessage(':step :stepper', { step: 'x', stepper: 'y' })).toBe('x y');
    });
});

describe('announcerMixin', () => {
    it('sets the announcement for the live region', () => {
        const announcer = announcerMixin();

        expect(announcer.announce('Added A-1 (1 items)')).toBe(true);
        expect(announcer.announcement).toBe('Added A-1 (1 items)');
    });

    it('skips an identical announcement within 3 seconds', () => {
        const announcer = announcerMixin();
        announcer.announce('Rejected: duplicate');

        vi.advanceTimersByTime(2999);

        expect(announcer.announce('Rejected: duplicate')).toBe(false);
    });

    it('clears and re-sets an identical announcement later so it is read again', () => {
        const announcer = announcerMixin();
        announcer.announce('Rejected: duplicate');
        vi.advanceTimersByTime(3000);

        expect(announcer.announce('Rejected: duplicate')).toBe(true);
        expect(announcer.announcement).toBe('');

        vi.runAllTimers();

        expect(announcer.announcement).toBe('Rejected: duplicate');
    });

    it('announces a different message straight away', () => {
        const announcer = announcerMixin();
        announcer.announce('Added A-1 (1 items)');

        expect(announcer.announce('Added A-2 (2 items)')).toBe(true);
        expect(announcer.announcement).toBe('Added A-2 (2 items)');
    });
});

function mountCollector(options = {}) {
    return mountComponent(qrCollectorComponent({
        sound: false, vibrate: false, hardwareScanner: false, delayBetweenScansMs: 0, ...options,
    }), { element: document.body });
}

// delayBetweenScansMs 0 still releases the processing lock on a timer.
function scan(collector, code) {
    collector.handleDetectedCode(code);
    vi.advanceTimersByTime(1);
}

describe('collector announcements', () => {
    it('announces an accepted code with the item count', () => {
        const collector = mountCollector({ addedAnnouncement: 'Ditambahkan :code (:count item)' });

        scan(collector, 'A-1');
        scan(collector, 'A-2');

        expect(collector.announcement).toBe('Ditambahkan A-2 (2 item)');
    });

    it('announces a duplicate as rejected, once while the code stays in view', () => {
        const collector = mountCollector();
        const rejected = formatMessage(fallbackMessages.rejectedAnnouncement, { reason: fallbackMessages.duplicateMessage });

        scan(collector, 'A-1');
        scan(collector, 'A-2');
        scan(collector, 'A-1');

        expect(collector.announcement).toBe(rejected);
        expect(collector.announce(rejected)).toBe(false);
    });

    it('does not reject the code just added while the camera still sees it', () => {
        const collector = mountCollector();

        scan(collector, 'A-1');
        scan(collector, 'A-1');

        expect(collector.announcement).toBe(formatMessage(fallbackMessages.addedAnnouncement, { code: 'A-1', count: 1 }));
    });

    it('uses the translated decoder error from the payload', async () => {
        const collector = mountCollector({ decoderFailedMessage: 'Dekoder kamera gagal dimuat.' });

        await collector.loadCameras();

        expect(collector.errorMessage).toBe('Dekoder kamera gagal dimuat.');
    });
});

const fields = [{ key: 'po', label: 'PO' }, { key: 'sku', label: 'SKU' }];

function mountSequence(options = {}, respond = null) {
    const wire = createWireSpy({ respond: respond ?? (() => null) });

    return mountComponent(qrScanSequenceComponent({
        fields, sound: false, vibrate: false, hardwareScanner: false, ...options,
    }), { element: document.body, wire });
}

describe('sequence announcements', () => {
    it('announces each accepted step and the completed sequence', () => {
        const sequence = mountSequence();

        sequence.processScan('PO-1', 'hardware');
        expect(sequence.announcement).toBe('Added PO-1 to PO');

        sequence.processScan('SKU-1', 'hardware');
        expect(sequence.announcement).toBe(`Added SKU-1 to SKU. ${fallbackMessages.completedAnnouncement}`);
    });

    it('announces a server rejection with its reason', async () => {
        const sequence = mountSequence({ serverCheck: true, componentKey: 'form.receipt', rejectedAnnouncement: 'Ditolak: :reason' }, () => ({
            passes: false, code: 'X', step: 'po', next_step: 'po', message: 'Unknown PO', values: {},
        }));

        await sequence.processScan('X', 'hardware');

        expect(sequence.announcement).toBe('Ditolak: Unknown PO');
    });

    it('announces a server-accepted step', async () => {
        const sequence = mountSequence({ serverCheck: true, componentKey: 'form.receipt' }, (key, method, args) => ({
            passes: true, code: args.code, step: 'po', next_step: 'sku', values: { po: args.code }, skipped: [],
        }));

        await sequence.processScan('PO-9', 'hardware');

        expect(sequence.announcement).toBe('Added PO-9 to PO');
    });

    it('uses the translated camera errors from the payload', async () => {
        const sequence = mountSequence({ decoderFailedMessage: 'Dekoder kamera gagal dimuat.' });

        await sequence.loadCameras();

        expect(sequence.errorMessage).toBe('Dekoder kamera gagal dimuat.');
    });
});
