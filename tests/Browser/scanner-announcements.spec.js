import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');

const field = (page, name) => page.locator(`[data-field-name="${name}"]`).first();

const wedge = async (page, code) => {
    await page.keyboard.type(code, { delay: 8 });
    await page.keyboard.press('Enter');
};

test('a single scanner field announces an accepted wedge scan', async ({ page }) => {
    await page.goto('/workbench/scan-smoke');
    const wrapper = field(page, 'wedge');
    const announcer = wrapper.locator('[data-qr-part="announcer"]');
    await expect(announcer).toHaveAttribute('aria-live', 'polite');
    await expect(announcer).toHaveAttribute('role', 'status');

    await wrapper.locator('[data-qr-part="scanner.input"]').focus();
    await wedge(page, 'WEDGE-0003');

    await expect(announcer).toHaveText('Added WEDGE-0003 to Wedge');
});

test('a single scanner field announces a rejected scan with the rejection message', async ({ page }) => {
    await page.goto('/workbench/scan-smoke');
    const wrapper = field(page, 'wedge');
    const announcer = wrapper.locator('[data-qr-part="announcer"]');

    await wrapper.locator('[data-qr-part="scanner.input"]').focus();
    await wedge(page, 'NOPE-2');

    // The validation message from the server (starts_with:WEDGE-), not a generic one.
    await expect(announcer).toHaveText(/^Rejected: .*WEDGE-/);
    await expect(page.getByTestId('state')).not.toContainText('NOPE-2');
});

test('a single scanner field announces a camera scan', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg });
    await page.goto('/workbench/scan-smoke');
    const wrapper = field(page, 'camera');

    await wrapper.locator('[data-qr-part="scanner.camera-button"]').click();

    await expect(wrapper.locator('[data-qr-part="announcer"]')).toHaveText('Added CAM-SMOKE-42 to Camera', { timeout: 15_000 });
});
