/**
 * English fallbacks for every user-facing string, keyed by scan-payload
 * option name. The server sends the translated `filament-qr-code::ui.*`
 * strings in the payload (Support\ScanMessages); these apply only when a
 * component mounts without one. A Pest test keeps them equal to the en file.
 */
export const fallbackMessages = Object.freeze({
    insecureMessage: 'Camera needs a secure context: serve this page over https or open it on localhost, then allow camera access.',
    fileScanFailedMessage: 'No barcode or QR code found in the selected image.',
    decoderFailedMessage: 'Failed to load the camera decoder.',
    cameraUnavailableMessage: 'Camera access denied or unavailable.',
    noCamerasMessage: 'No camera devices detected on this system.',
    cameraStartFailedMessage: 'Failed to start the camera.',
    duplicateMessage: 'Duplicate codes are not allowed.',
    addedAnnouncement: 'Added :code (:count items)',
    stepAddedAnnouncement: 'Added :code to :step',
    completedAnnouncement: 'Sequence complete.',
    rejectedAnnouncement: 'Rejected: :reason',
    scanFailedMessage: 'The scan could not be checked. Please try again.',
});

/** The payload's messages over the fallbacks; undefined or empty values fall back. */
export function messagesFrom(options = {}) {
    return Object.fromEntries(Object.entries(fallbackMessages).map(([name, fallback]) => {
        const value = options[name];

        return [name, typeof value === 'string' && value !== '' ? value : fallback];
    }));
}
