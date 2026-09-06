import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'resources/js/index.js'),
            name: 'FilamentQrCode',
            fileName: () => 'filament-qr-code.js',
            formats: ['iife'],
        },
        rollupOptions: {
            output: {
                assetFileNames: 'filament-qr-code.[ext]',
            },
        },
    },
});
