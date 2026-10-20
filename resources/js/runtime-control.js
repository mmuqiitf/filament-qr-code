/**
 * Shared runtime control for scan components: options that follow the
 * server-rendered data-qr-options attribute, and window events aimed at one field.
 */

const SOURCES = ['camera', 'hardware', 'upload', 'manual'];

// A fed code counts as a device scan unless the caller names another source.
export function feedSource(source) {
    if (source === undefined || source === null) {
        return 'hardware';
    }

    return SOURCES.includes(source) ? source : 'manual';
}

function optionsHost(element) {
    return element?.closest?.('[data-qr-options]') ?? null;
}

export function readOptionsAttribute(element) {
    const raw = optionsHost(element)?.getAttribute('data-qr-options');

    if (!raw) {
        return null;
    }

    try {
        const options = JSON.parse(raw);

        return options && typeof options === 'object' ? options : null;
    } catch {
        return null;
    }
}

/**
 * Call onChange(options) whenever Livewire morphs a new data-qr-options value
 * onto the host. Identical payloads are ignored. Returns a disconnect function.
 */
export function observeOptions(element, onChange) {
    const host = optionsHost(element);

    if (!host || typeof MutationObserver === 'undefined') {
        return () => {};
    }

    let last = host.getAttribute('data-qr-options');
    const observer = new MutationObserver(() => {
        const current = host.getAttribute('data-qr-options');

        if (current === last) {
            return;
        }

        last = current;
        const options = readOptionsAttribute(element);

        if (options) {
            onChange(options);
        }
    });

    observer.observe(host, { attributes: true, attributeFilter: ['data-qr-options'] });

    return () => observer.disconnect();
}

/**
 * Listen for window control events. A handler runs when the event names no
 * field or names one of this component's identities.
 */
export function listenForControlEvents(handlers, identities) {
    const listeners = Object.entries(handlers).map(([name, handler]) => {
        const listener = (event) => {
            const detail = event.detail ?? {};
            const field = detail.field;

            if (field !== undefined && field !== null && field !== '' && !identities().includes(field)) {
                return;
            }

            handler(detail);
        };

        window.addEventListener(name, listener);

        return [name, listener];
    });

    return () => listeners.forEach(([name, listener]) => window.removeEventListener(name, listener));
}

export function changedKeys(previous, next) {
    return Object.keys(next).filter((key) => JSON.stringify(previous[key]) !== JSON.stringify(next[key]));
}
