import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        rollupOptions: {
            input: resolve(__dirname, 'resources/js/index.js'),
            output: {
                entryFileNames: 'filament-qr-code.js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'filament-qr-code.[ext]',
                format: 'es',
            },
        },
    },
});
