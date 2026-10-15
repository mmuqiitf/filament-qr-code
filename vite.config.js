import { defineConfig } from 'vite';
import { resolve } from 'path';

/**
 * Main bundle. Filament publishes it as `filament-qr-code-scripts.js`, so it
 * must not depend on sibling chunks by their build names. The camera decoder
 * stays lazy: `import('html5-qrcode')` is rewritten to the self-contained
 * `filament-qr-code-decoder.js` built by vite.decoder.config.js and
 * registered as a load-on-request Filament asset next to this file.
 */
export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        rollupOptions: {
            input: resolve(import.meta.dirname, 'resources/js/index.js'),
            external: ['html5-qrcode'],
            output: {
                entryFileNames: 'filament-qr-code.js',
                assetFileNames: 'filament-qr-code.[ext]',
                format: 'es',
                paths: {
                    'html5-qrcode': './filament-qr-code-decoder.js',
                },
            },
        },
    },
});
