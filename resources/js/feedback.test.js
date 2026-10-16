// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { qrFeedback } from './audio-feedback.js';
import { ERROR_TONES, FLASH_CLASSES, FLASH_MS, flashElement, playOutcomeFeedback } from './feedback.js';
import { createWireSpy, mountComponent } from './alpine-test-harness.js';

const successFeedback = { sound: true, vibrate: [100], flash: true };
const errorFeedback = { sound: true, vibrate: [80, 60, 80], flash: true };

function recordFeedback() {
    const played = [];
    vi.spyOn(qrFeedback, 'playTones').mockImplementation((tones) => played.push(['tones', tones]));
    vi.spyOn(qrFeedback, 'vibratePattern').mockImplementation((pattern) => played.push(['vibrate', pattern]));
    vi.spyOn(qrFeedback, 'trigger').mockImplementation((options) => played.push(['legacy', options]));

    return played;
}

function mountScanner({ respond, ...options } = {}) {
    document.body.innerHTML = '<div data-field-name="sku"><input type="text" wire:model="data.sku"></div>';
    const element = document.querySelector('[data-field-name="sku"]');
    const wire = createWireSpy({ respond });
    const scanner = mountComponent(
        qrScannerComponent({
            statePath: 'data.sku',
            hardwareScanner: false,
            serverCheck: true,
            componentKey: 'form.sku',
            cooldownMs: 1500,
            beepFrequency: 880,
            beepDurationMs: 80,
            successFeedback,
            errorFeedback,
            ...options,
        }),
        { element, wire },
    );

    return { scanner, element };
}

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    document.body.innerHTML = '';
});

describe('scanner outcome feedback', () => {
    it('rejected result plays error tone and adds danger flash class', async () => {
        const played = recordFeedback();
        const { scanner, element } = mountScanner({
            respond: () =>
                Promise.resolve({ passes: false, code: 'X', source: 'camera', message: 'Nope', record_key: null }),
        });

        await scanner.handleScanResult('X');

        expect(played).toEqual([
            ['tones', ERROR_TONES],
            ['vibrate', [80, 60, 80]],
        ]);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(true);

        vi.advanceTimersByTime(FLASH_MS);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(false);
    });

    it('accepted result plays the success tone, success vibration and success flash', async () => {
        const played = recordFeedback();
        const { scanner, element } = mountScanner({
            respond: () =>
                Promise.resolve({ passes: true, code: 'A', source: 'camera', message: null, record_key: null }),
        });

        await scanner.handleScanResult('A');

        expect(played).toEqual([
            ['tones', [{ frequency: 880, durationMs: 80 }]],
            ['vibrate', [100]],
        ]);
        expect(element.classList.contains(FLASH_CLASSES.success)).toBe(true);
    });

    it('a failed server call gets the error feedback', async () => {
        const played = recordFeedback();
        vi.spyOn(console, 'error').mockImplementation(() => {});
        const { scanner, element } = mountScanner({ respond: () => Promise.reject(new Error('offline')) });

        await scanner.handleScanResult('X');

        expect(played[0]).toEqual(['tones', ERROR_TONES]);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(true);
    });

    it('a duplicate is silent and does not flash', async () => {
        const { scanner, element } = mountScanner({
            respond: () =>
                Promise.resolve({ passes: true, code: 'A', source: 'camera', message: null, record_key: null }),
        });
        await scanner.handleScanResult('A');
        vi.advanceTimersByTime(FLASH_MS);
        const played = recordFeedback();

        await scanner.handleScanResult('A');

        expect(played).toEqual([]);
        expect(element.className).toBe('');
    });

    it('keeps the legacy beep and no flash for fields without checks', () => {
        const played = recordFeedback();
        const { scanner, element } = mountScanner({ serverCheck: false, cooldownMs: 0 });

        scanner.handleScanResult('PRD-1');

        expect(played.map(([kind]) => kind)).toEqual(['legacy']);
        expect(element.className).toBe('');
    });
});

describe('playOutcomeFeedback', () => {
    it('honours disabled sound, empty vibration and disabled flash', () => {
        const played = [];
        const feedback = {
            playTones: (tones) => played.push(['tones', tones]),
            vibratePattern: (pattern) => played.push(['vibrate', pattern]),
        };
        const element = document.createElement('div');

        playOutcomeFeedback('error', { sound: false, vibrate: [], flash: false }, { element, feedback });

        expect(played).toEqual([]);
        expect(element.className).toBe('');
    });

    it('does nothing without settings', () => {
        const played = [];
        const feedback = { playTones: () => played.push('tones'), vibratePattern: () => played.push('vibrate') };

        playOutcomeFeedback('success', null, { feedback });

        expect(played).toEqual([]);
    });

    it('replaces a running flash with the new outcome', () => {
        const element = document.createElement('div');

        flashElement(element, FLASH_CLASSES.success);
        flashElement(element, FLASH_CLASSES.error);

        expect([...element.classList]).toEqual([FLASH_CLASSES.error]);
    });
});

describe('QrFeedback', () => {
    it('vibratePattern passes the pattern to navigator.vibrate', () => {
        const patterns = [];
        Object.defineProperty(navigator, 'vibrate', { value: (pattern) => patterns.push(pattern), configurable: true });

        qrFeedback.vibratePattern([80, 60, 80]);

        expect(patterns).toEqual([[80, 60, 80]]);
    });

    it('playTones does not throw without an AudioContext', () => {
        expect(() => qrFeedback.playTones(ERROR_TONES)).not.toThrow();
    });
});

describe('flash visibility around the scanner modal', () => {
    function mountWithModal(respond) {
        const mounted = mountScanner({ respond });
        mounted.element.insertAdjacentHTML('beforeend', '<div data-qr-scanner-panel></div>');
        mounted.panel = mounted.element.querySelector('[data-qr-scanner-panel]');
        mounted.scanner.isModalOpen = true;
        mounted.scanner.stopScan = () => new Promise((resolve) => setTimeout(resolve, 400));

        return mounted;
    }

    const reject = () =>
        Promise.resolve({ passes: false, code: 'X', source: 'upload', message: 'Nope', record_key: null });

    it('flashes the field after the scanner modal closes, not behind it', async () => {
        recordFeedback();
        const { scanner, element } = mountWithModal(reject);

        await scanner.handleScanResult('X', 'upload');
        scanner.closeScannerModal();

        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(false);

        await vi.advanceTimersByTimeAsync(400);

        expect(scanner.isModalOpen).toBe(false);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(true);

        vi.advanceTimersByTime(FLASH_MS - 1);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(true);
    });

    it('camera reject with the modal open flashes the panel and leaves no pending field flash', async () => {
        recordFeedback();
        const { scanner, element, panel } = mountWithModal(reject);
        scanner.html5Qrcode = { pause: () => {}, resume: () => {} };

        await scanner.onCameraDecode('X');

        expect(panel.classList.contains(FLASH_CLASSES.error)).toBe(true);
        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(false);

        vi.advanceTimersByTime(FLASH_MS);
        scanner.closeScannerModal();
        await vi.advanceTimersByTimeAsync(400);

        expect(element.classList.contains(FLASH_CLASSES.error)).toBe(false);
    });

    it('keeps the ring long enough to notice', () => {
        expect(FLASH_MS).toBeGreaterThanOrEqual(900);
    });
});
