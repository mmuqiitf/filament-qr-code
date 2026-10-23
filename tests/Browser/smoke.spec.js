import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');

const field = (page, name) => page.locator(`[data-field-name="${name}"]`).first();

test('synthetic 8 ms burst fills field and shows success flash', async ({ page }) => {
    await page.goto('/workbench/scan-smoke');

    const wrapper = field(page, 'wedge');
    const input = wrapper.locator('[data-qr-part="scanner.input"]');
    await input.focus();

    await page.keyboard.type('WEDGE-0001', { delay: 8 });
    await page.keyboard.press('Enter');

    await expect(input).toHaveValue('WEDGE-0001');
    await expect(wrapper).toHaveClass(/fi-qr-flash-success/);
    await expect(page.getByTestId('state')).toContainText('"wedge":"WEDGE-0001"');
});

test('fake camera stream decodes into the field', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg });
    await page.goto('/workbench/scan-smoke');

    const wrapper = field(page, 'camera');
    await wrapper.locator('[data-qr-part="scanner.camera-button"]').click();

    await expect(wrapper.locator('[data-qr-part="scanner.input"]')).toHaveValue('CAM-SMOKE-42', { timeout: 15_000 });
    await expect(page.getByTestId('state')).toContainText('"camera":"CAM-SMOKE-42"');
});

test('torch and zoom controls follow camera capabilities', async ({ page }) => {
    await page.addInitScript(installFakeCamera, {
        svg: '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>',
        capabilities: { torch: true, zoom: { min: 1, max: 4, step: 1 } },
    });
    await page.goto('/workbench/scan-smoke');

    const wrapper = field(page, 'camera');
    await wrapper.locator('[data-qr-part="scanner.camera-button"]').click();

    const controls = wrapper.locator('[data-qr-part="camera.controls"]');
    await expect(controls).toBeVisible({ timeout: 10_000 });

    await controls.locator('.fi-qr-camera-torch').click();
    await expect(controls.locator('.fi-qr-camera-torch')).toHaveAttribute('aria-pressed', 'true');
    await controls.locator('.fi-qr-camera-zoom').fill('3');

    const constraints = await page.evaluate(() => window.__fakeCameraConstraints);
    expect(constraints).toContainEqual({ advanced: [{ torch: true }] });
    expect(constraints).toContainEqual({ advanced: [{ zoom: 3 }] });
});
