/**
 * Minimal Alpine-like harness for unit tests: mounts a component object with
 * $el, $root, $wire, $id, $nextTick, and a $watch that fires synchronously when a
 * watched property changes (Alpine fires watchers on change).
 */
export function mountComponent(component, { element, wire = null, currentElement = null } = {}) {
    const watchers = {};

    const context = {
        // Alpine sets $el to the element whose directive runs; $root is the x-data element.
        $el: currentElement ?? element,
        $root: element,
        $wire: wire,
        $id: (name) => `${name}-1`,
        $nextTick: (callback) => callback(),
        $watch(property, callback) {
            watchers[property] = watchers[property] || [];
            watchers[property].push(callback);
        },
    };

    const proxy = new Proxy(Object.assign(component, context), {
        set(target, property, value) {
            const previous = target[property];
            target[property] = value;

            if (previous !== value) {
                (watchers[property] || []).forEach((callback) => callback(value, previous));
            }

            return true;
        },
    });

    proxy.init?.call(proxy);

    return proxy;
}

export function createWireSpy({ respond = () => null } = {}) {
    const calls = [];
    const methodCalls = [];

    return {
        calls,
        methodCalls,
        set(path, value) {
            calls.push([path, value]);
        },
        callSchemaComponentMethod(componentKey, method, args) {
            methodCalls.push([componentKey, method, args]);

            return respond(componentKey, method, args);
        },
    };
}
