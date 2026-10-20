export function createScanDeduper({ now = () => Date.now() } = {}) {
    let lastCode = null;
    let lastDeliveredAt = 0;

    return {
        isDuplicate(code, cooldownMs) {
            const currentTime = now();

            if (cooldownMs > 0 && code === lastCode && currentTime - lastDeliveredAt < cooldownMs) {
                return true;
            }

            lastCode = code;
            lastDeliveredAt = currentTime;

            return false;
        },

        reset() {
            lastCode = null;
            lastDeliveredAt = 0;
        },
    };
}

export function normalizeScanResult(result, { code, source, scanFailedMessage }) {
    if (result === null || typeof result !== 'object' || typeof result.passes !== 'boolean') {
        return { passes: false, code, source, message: scanFailedMessage, recordKey: null };
    }

    return {
        passes: result.passes,
        code: result.code ?? code,
        source: result.source ?? source,
        message: result.message ?? null,
        recordKey: result.record_key ?? null,
    };
}

export async function requestScanCheck(wire, { componentKey, code, source, scanFailedMessage }) {
    try {
        const result = await wire.callSchemaComponentMethod(componentKey, 'handleScan', { code, source });

        return normalizeScanResult(result, { code, source, scanFailedMessage });
    } catch (error) {
        console.error('QR scan check failed:', error);

        return normalizeScanResult(null, { code, source, scanFailedMessage });
    }
}
