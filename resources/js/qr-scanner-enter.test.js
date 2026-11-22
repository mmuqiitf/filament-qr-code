// @vitest-environment happy-dom
import { afterEach, describe, expect, it, vi } from 'vitest';
import qrScannerComponent from './qr-scanner.js';
import { mountComponent } from './alpine-test-harness.js';

// Enter in a checked scanner field sends the typed value first. A value the
// server rejects (rejectWhen(), gs1()) must not also submit the form, which
// would bury the reason under required-field errors.
function mountTyped({ commit, serverCheck = true, mode = 'single' } = {}) {
    document.body.innerHTML = '<form><div data-field-name="label"><input type="text" wire:model="data.label"></div><button>Go</button></form>';
    const form = document.querySelector('form');
    form.requestSubmit = vi.fn();
    const element = document.querySelector('[data-field-name="label"]');
    const state = { 'data.label': null };
    const wire = {
        set: vi.fn(),
        get: (path) => state[path],
        $commit: vi.fn(async () => commit?.(state)),
        callSchemaComponentMethod: vi.fn(),
    };
    const scanner = mountComponent(
        qrScannerComponent({ statePath: 'data.label', sound: false, vibrate: false, hardwareScanner: false, serverCheck, componentKey: 'form.label', mode }),
        { element, wire },
    );
    const input = element.querySelector('input');
    const enter = () => {
        const event = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
        input.dispatchEvent(event);

        return event;
    };

    return { scanner, wire, form, input, enter };
}

afterEach(() => {
    document.body.innerHTML = '';
});

describe('Enter on a typed scanner value', () => {
    it('does not submit the form when the server rejects the typed value', async () => {
        const { scanner, form, input, enter } = mountTyped({
            commit: (state) => {
                state['data.label'] = null;
                window.dispatchEvent(new CustomEvent('qr-scan-rejected', { detail: { message: 'Plain product barcode, not a GS1 label' } }));
            },
        });
        scanner.value = '8901234567074';

        const event = await scanner.submitTyped({ ...enterEvent(input), preventDefault: vi.fn() });

        expect(form.requestSubmit).not.toHaveBeenCalled();
        expect(event).toBe(false);
    });

    it('submits the form once the server accepted the typed value', async () => {
        const { scanner, wire, form, input } = mountTyped({
            commit: (state) => {
                state['data.label'] = '(01)08901234567074(17)271231';
            },
        });
        scanner.value = '0108901234567074 17271231';
        const preventDefault = vi.fn();

        expect(await scanner.submitTyped({ ...enterEvent(input), preventDefault })).toBe(true);
        expect(preventDefault).toHaveBeenCalled();
        expect(wire.$commit).toHaveBeenCalledTimes(1);
        expect(form.requestSubmit).toHaveBeenCalledTimes(1);
    });

    it('treats a typed value the server cleared as rejected', async () => {
        const { scanner, form, input } = mountTyped({ commit: (state) => { state['data.label'] = null; } });
        scanner.value = 'BAD-1';

        await scanner.submitTyped({ ...enterEvent(input), preventDefault: vi.fn() });

        expect(form.requestSubmit).not.toHaveBeenCalled();
    });

    it.each([
        ['an empty field', { value: '' }],
        ['a field without server checks', { serverCheck: false }],
        ['continuous mode', { mode: 'continuous' }],
        ['an Enter a hardware burst already handled', { defaultPrevented: true }],
    ])('leaves the native Enter alone for %s', async (_, { value = 'X-1', defaultPrevented = false, ...options }) => {
        const { scanner, wire, form, input } = mountTyped(options);
        scanner.value = value;
        const preventDefault = vi.fn();

        expect(await scanner.submitTyped({ ...enterEvent(input), defaultPrevented, preventDefault })).toBeUndefined();
        expect(preventDefault).not.toHaveBeenCalled();
        expect(wire.$commit).not.toHaveBeenCalled();
        expect(form.requestSubmit).not.toHaveBeenCalled();
    });
});

function enterEvent(input) {
    return { key: 'Enter', target: input, defaultPrevented: false, isComposing: false };
}
