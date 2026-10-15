import { defineConfig } from 'vite';
import { resolve } from 'path';

/**
 * Self-contained html5-qrcode ES module, loaded on first camera use.
 * Built after vite.config.js into the same folder (no emptyOutDir).
 */
export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: false,
        copyPublicDir: false,
        lib: {
            entry: resolve(import.meta.dirname, 'resources/js/decoder-entry.js'),
            formats: ['es'],
            fileName: () => 'filament-qr-code-decoder.js',
        },
    },
});
