const FIELD_ELEMENT_SELECTOR = 'input, textarea, select';

/**
 * Return the Livewire state path bound to an element through any
 * wire:model modifier (wire:model, .live, .blur, .live.debounce.500ms, ...).
 */
export function readWireModelPath(element) {
    if (!element?.attributes) {
        return null;
    }

    for (const attribute of element.attributes) {
        if (attribute.name === 'wire:model' || attribute.name.startsWith('wire:model.')) {
            return attribute.value;
        }
    }

    return null;
}

function matchesStatePath(statePath, fieldName) {
    if (!statePath) {
        return false;
    }

    return statePath === fieldName || statePath.endsWith(`.${fieldName}`);
}

/**
 * Locate a field's input by exact attribute comparison rather than by
 * interpolated CSS selectors, so nested paths (items.0.sku) and names with
 * CSS metacharacters resolve, and `sku` never matches `parent_sku`.
 */
export function findFieldInput(fieldName, root = document) {
    if (!fieldName) {
        return null;
    }

    const wrappers = Array.from(root.querySelectorAll('[data-field-name]'));
    const wrapper = wrappers.find((element) => element.getAttribute('data-field-name') === fieldName);
    const wrappedInput = wrapper?.querySelector(FIELD_ELEMENT_SELECTOR);

    if (wrappedInput) {
        return wrappedInput;
    }

    const candidates = Array.from(root.querySelectorAll(FIELD_ELEMENT_SELECTOR));

    return candidates.find((element) => element.getAttribute('name') === fieldName)
        || candidates.find((element) => element.id === fieldName)
        || candidates.find((element) => matchesStatePath(readWireModelPath(element), fieldName))
        || null;
}
