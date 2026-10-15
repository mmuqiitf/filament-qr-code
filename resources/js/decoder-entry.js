/**
 * Self-contained html5-qrcode entry for Filament asset publishing.
 *
 * Filament renames the main bundle on publish (`filament-qr-code-scripts.js`),
 * so a Vite/Rolldown lazy chunk that imports helpers from the entry would 404.
 * Building the decoder as its own ES module keeps camera pages lazy while
 * staying a sibling file that `filament:assets` can copy.
 */
export { Html5Qrcode, Html5QrcodeSupportedFormats, Html5QrcodeScannerState } from 'html5-qrcode';
