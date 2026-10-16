// @vitest-environment happy-dom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { qrHardwareScannerListenerComponent } from './qr-hardware-scanner.js';
import { mountComponent } from './alpine-test-harness.js';

function mountListener(options = {}) {
    const element = document.createElement('div');
    document.body.appendChild(element);

    return mountComponent(
        qrHardwareScannerListenerComponent({ sound: false, vibrate: false, autoFocusNext: false, ...options }),
        { element },
    );
}

// A keyboard-wedge burst: each key lands in the focused input before the
// listener can tell it is a scan, then Enter terminates it.
function scanBurst(text) {
    for (const key of text) {
        vi.advanceTimersByTime(10);
        const target = document.activeElement;
        target.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
        if (target && 'value' in target) {
            target.value += key;
        }
    }

    vi.advanceTimersByTime(10);
    document.activeElement.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
}

function recordEvents(element) {
    const events = [];
    element.addEventListener('input', () => events.push('input'));
    element.addEventListener('change', () => events.push('change'));

    return events;
}

const cashierMarkup = `
    <form>
        <input type="text" name="customerCode" value="MEMBER-7">
        <div data-field-name="scanInput"><input type="text" name="scanInput"></div>
    </form>`;

beforeEach(() => {
    vi.useFakeTimers({ now: 1_000_000 });
});

afterEach(() => {
    vi.useRealTimers();
    document.body.innerHTML = '';
});

describe('qrHardwareScannerListener focused-input routing', () => {
    it('routes a burst to the registered field and restores a focused unrelated input', () => {
        document.body.innerHTML = cashierMarkup;
        const listener = mountListener({ fields: ['scanInput'], fallbackToAnyInput: false });
        const member = document.querySelector('[name="customerCode"]');
        const memberEvents = recordEvents(member);
        member.focus();

        scanBurst('PRD-1003');

        expect(document.querySelector('[name="scanInput"]').value).toBe('PRD-1003');
        expect(member.value).toBe('MEMBER-7');
        expect(memberEvents).toEqual(['input', 'change']);

        listener.destroy();
    });

    it('still delivers into a focused registered field', () => {
        document.body.innerHTML = cashierMarkup;
        const listener = mountListener({ fields: ['scanInput'] });
        const scanInput = document.querySelector('[name="scanInput"]');
        scanInput.focus();

        scanBurst('PRD-1001');

        expect(scanInput.value).toBe('PRD-1001');
        expect(document.querySelector('[name="customerCode"]').value).toBe('MEMBER-7');

        listener.destroy();
    });

    it('keeps delivering into the focused input when no fields are registered', () => {
        document.body.innerHTML = '<form><input type="text" name="note"></form>';
        const listener = mountListener({ fields: [] });
        const note = document.querySelector('[name="note"]');
        note.focus();

        scanBurst('PRD-1002');

        expect(note.value).toBe('PRD-1002');

        listener.destroy();
    });
});
