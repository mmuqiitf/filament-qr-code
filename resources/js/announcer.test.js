// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { COALESCE_WINDOW_MS, announcerMixin, formatMessage } from './announcer.js';
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

    it('announces a different message straight away once the region is quiet', () => {
        const announcer = announcerMixin();
        announcer.announce('Added A-1 (1 items)');
        vi.advanceTimersByTime(COALESCE_WINDOW_MS);

        expect(announcer.announce('Added A-2 (2 items)')).toBe(true);
        expect(announcer.announcement).toBe('Added A-2 (2 items)');
    });

    it('coalesces a burst so only the latest message is read', () => {
        const announcer = announcerMixin();
        const written = [];
        const write = announcer.writeAnnouncement.bind(announcer);
        announcer.writeAnnouncement = (message) => {
            written.push(message);
            write(message);
        };

        announcer.announce('Added A-1 (1 items)');
        announcer.announce('Added A-2 (2 items)');
        announcer.announce('Added A-3 (3 items)');

        expect(announcer.announcement).toBe('Added A-1 (1 items)');

        vi.advanceTimersByTime(COALESCE_WINDOW_MS);

        expect(announcer.announcement).toBe('Added A-3 (3 items)');
        expect(written).toEqual(['Added A-1 (1 items)', 'Added A-3 (3 items)']);
    });

    it('replaces the region text instead of appending to it', () => {
        const announcer = announcerMixin();
        announcer.announce('Added A-1 (1 items)');
        announcer.announce('Rejected: duplicate');
        vi.advanceTimersByTime(COALESCE_WINDOW_MS);

        expect(announcer.announcement).toBe('Rejected: duplicate');
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
    vi.advanceTimersByTime(COALESCE_WINDOW_MS);
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

    it('stays silent when announcements are off', () => {
        const collector = mountCollector({ announcements: false });

        scan(collector, 'A-1');

        expect(collector.announcement).toBe('');
    });

    it('reads custom text from the server, falling back to the default on null', async () => {
        const wire = createWireSpy({ respond: (key, method, args) => (args.code === 'A-1' ? 'Box A-1 in' : null) });
        const collector = mountComponent(qrCollectorComponent({
            sound: false, vibrate: false, hardwareScanner: false, delayBetweenScansMs: 0,
            customAnnouncements: true, componentKey: 'form.codes',
        }), { element: document.body, wire });

        scan(collector, 'A-1');
        await vi.runAllTimersAsync();
        expect(collector.announcement).toBe('Box A-1 in');
        expect(wire.methodCalls[0]).toEqual(['form.codes', 'announceScan', { code: 'A-1', accepted: true, reason: null, field: null }]);

        scan(collector, 'A-2');
        await vi.runAllTimersAsync();
        expect(collector.announcement).toBe(formatMessage(fallbackMessages.addedAnnouncement, { code: 'A-2', count: 2 }));
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
        vi.advanceTimersByTime(COALESCE_WINDOW_MS);
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

    it('stays silent when announcements are off', () => {
        const sequence = mountSequence({ announcements: false });

        sequence.processScan('PO-1', 'hardware');

        expect(sequence.announcement).toBe('');
    });

    it('reads custom text for a step from the server', async () => {
        const sequence = mountSequence({ customAnnouncements: true, componentKey: 'form.receipt' }, (key, method, args) => (
            method === 'announceScan' ? `Step ${args.field}: ${args.code}` : null
        ));

        sequence.processScan('PO-1', 'hardware');
        await vi.runAllTimersAsync();

        expect(sequence.announcement).toBe('Step po: PO-1');
    });

    it('uses the translated camera errors from the payload', async () => {
        const sequence = mountSequence({ decoderFailedMessage: 'Dekoder kamera gagal dimuat.' });

        await sequence.loadCameras();

        expect(sequence.errorMessage).toBe('Dekoder kamera gagal dimuat.');
    });
});
