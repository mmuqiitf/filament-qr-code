export const REJECTION_LIMIT = 5;

export function rememberRejection(reasons, message) {
    const reason = typeof message === 'string' ? message.trim() : '';

    if (reason === '') {
        return reasons;
    }

    return [reason, ...reasons].slice(0, REJECTION_LIMIT);
}

export function bindOnlineState(component) {
    component.isOnline = typeof navigator === 'undefined' ? true : navigator.onLine !== false;
    component.offlineMode = component.options?.offline ?? component.offlineMode ?? component.offline ?? 'banner';

    const onOnline = () => {
        component.isOnline = true;
    };
    const onOffline = () => {
        component.isOnline = false;
    };

    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
    component.unbindOnlineState = () => {
        window.removeEventListener('online', onOnline);
        window.removeEventListener('offline', onOffline);
    };
}

export function offlineBlockResult(component, code, source) {
    const mode = component.offlineMode ?? component.options?.offline ?? 'banner';

    if (component.isOnline !== false || mode !== 'block') {
        return null;
    }

    const message = component.options?.offlineBlockedMessage || component.offlineBlockedMessage;

    if (typeof message !== 'string' || message === '') {
        return null;
    }

    return { passes: false, code, source, message, checked: false };
}
