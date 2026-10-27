import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');

test('inline collector calls the page handleCollectorScan hook for each new code', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('response', (response) => response.status() >= 500 && errors.push(`${response.status()} ${response.url()}`));
    await page.addInitScript(installFakeCamera, { svg });
    await page.goto('/workbench/collector-smoke');

    const field = page.locator('[x-data^="qrCollector"]');
    await field.getByRole('button', { name: /start scanning/i }).click();

    await expect(field).toContainText('CAM-SMOKE-42', { timeout: 15_000 });
    await expect(page.getByTestId('handled')).toHaveText('["CAM-SMOKE-42"]');
    await page.waitForLoadState('networkidle');
    expect(errors).toEqual([]);
});
