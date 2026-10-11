import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        include: ['resources/js/**/*.test.js'],
        exclude: ['node_modules', 'vendor', 'resources/dist'],
    },
});
