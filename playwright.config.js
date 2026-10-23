import { defineConfig, devices } from '@playwright/test';

const port = Number(process.env.SMOKE_PORT ?? 8123);

export default defineConfig({
    testDir: 'tests/Browser',
    timeout: 30_000,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['list']] : 'list',
    use: {
        baseURL: `http://127.0.0.1:${port}`,
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: `php vendor/bin/testbench workbench:build && php vendor/bin/testbench serve --host=127.0.0.1 --port=${port}`,
        url: `http://127.0.0.1:${port}/workbench/scan-smoke`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
});
