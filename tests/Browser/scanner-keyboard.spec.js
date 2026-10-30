import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');
const blank = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>';

const insideModal = (page, fieldName) => page.evaluate(
    (name) => !!document.activeElement?.closest(`[data-field-name="${name}"] [data-qr-part="scanner.modal"]`),
    fieldName,
);

const focusedPart = (page) => page.evaluate(() => document.activeElement?.closest('[data-qr-part]')?.dataset.qrPart ?? null);

// Keyboard only: reach the camera button with Tab from the field's input, open with Enter.
const openWithKeyboard = async (page, fieldName) => {
    const field = page.locator(`[data-field-name="${fieldName}"]`);
    await field.locator('[data-qr-part="scanner.input"]').focus();
    await page.keyboard.press('Tab');
    expect(await focusedPart(page)).toBe('scanner.camera-button');
    await page.keyboard.press('Enter');
    await expect(field.locator('[data-qr-part="scanner.modal"]')).toBeVisible();

    return field;
};

test('Enter on the camera button opens the scanner and moves focus into the dialog', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg: blank });
    await page.goto('/workbench/scan-smoke');
    await openWithKeyboard(page, 'camera');

    await expect.poll(() => insideModal(page, 'camera')).toBe(true);
});

test('Tab and Shift+Tab stay inside the open scanner dialog', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg: blank });
    await page.goto('/workbench/scan-smoke');
    await openWithKeyboard(page, 'camera');
    await expect.poll(() => insideModal(page, 'camera')).toBe(true);

    for (const key of ['Tab', 'Tab', 'Tab', 'Tab', 'Tab', 'Shift+Tab', 'Shift+Tab', 'Shift+Tab', 'Shift+Tab', 'Shift+Tab', 'Shift+Tab']) {
        await page.keyboard.press(key);
        expect(await insideModal(page, 'camera'), `focus left the dialog after ${key}`).toBe(true);
    }
});

test('Escape closes the scanner and returns focus to the camera button', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg: blank });
    await page.goto('/workbench/scan-smoke');
    const field = await openWithKeyboard(page, 'camera');
    await expect.poll(() => insideModal(page, 'camera')).toBe(true);

    await page.keyboard.press('Escape');

    await expect(field.locator('[data-qr-part="scanner.modal"]')).toBeHidden();
    await expect(field.locator('[data-qr-part="scanner.camera-button"]')).toBeFocused();
});

test('the close button closes the scanner from the keyboard and restores focus', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg: blank });
    await page.goto('/workbench/scan-smoke');
    const field = await openWithKeyboard(page, 'camera');
    const close = field.locator('[data-qr-part="scanner.modal"] button[aria-label], [data-qr-part="scanner.modal"] button').first();
    await close.focus();
    await page.keyboard.press('Enter');

    await expect(field.locator('[data-qr-part="scanner.modal"]')).toBeHidden();
    await expect(field.locator('[data-qr-part="scanner.camera-button"]')).toBeFocused();
});

test('a camera scan closes the dialog and returns focus to the field', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg });
    await page.goto('/workbench/scan-smoke');
    const field = await openWithKeyboard(page, 'camera');

    await expect(field.locator('[data-qr-part="scanner.input"]')).toHaveValue('CAM-SMOKE-42', { timeout: 15_000 });
    await expect(field.locator('[data-qr-part="scanner.modal"]')).toBeHidden();
    await expect(field.locator('[data-qr-part="scanner.camera-button"]')).toBeFocused();
});

test('scan from image is reachable and operable from the keyboard', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg: blank });
    await page.goto('/workbench/scan-smoke');
    const field = await openWithKeyboard(page, 'camera');

    let reached = false;
    for (let i = 0; i < 8 && !reached; i++) {
        await page.keyboard.press('Tab');
        reached = await page.evaluate(() => document.activeElement?.matches('input[type="file"]') ?? false);
    }
    expect(reached, 'Tab never reached the image upload').toBe(true);
    // Visually hidden (not display:none, which would drop it from the tab order), and its label shows focus.
    const box = await page.evaluate(() => document.activeElement.getBoundingClientRect().toJSON());
    expect(box.width).toBeLessThanOrEqual(1);
    const outline = await field.locator('[data-qr-part="scanner.modal-actions"] label').evaluate((label) => getComputedStyle(label).outlineStyle);
    expect(outline).not.toBe('none');

    const chooser = page.waitForEvent('filechooser');
    await page.keyboard.press('Enter');
    await (await chooser).setFiles({ name: 'blank.svg', mimeType: 'image/svg+xml', buffer: Buffer.from(blank) });
});

test('typing a code by hand with the keyboard fills the field', async ({ page }) => {
    await page.goto('/workbench/scan-smoke');
    const field = page.locator('[data-field-name="wedge"]');
    const input = field.locator('[data-qr-part="scanner.input"]');
    await input.focus();
    await page.keyboard.type('WEDGE-HAND', { delay: 120 });
    await input.blur();

    await expect(page.getByTestId('state')).toContainText('"wedge":"WEDGE-HAND"');
});

test('Escape in a scanner opened inside an action modal closes only the scanner', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg: blank });
    await page.goto('/workbench/modal-smoke');
    await page.getByRole('button', { name: 'Scan in modal' }).click();
    const actionModal = page.locator('.fi-modal-window').filter({ visible: true });
    await expect(actionModal).toBeVisible();
    await actionModal.locator('[data-qr-part="scanner.camera-button"]').focus();
    await page.keyboard.press('Enter');
    const scanner = actionModal.locator('[data-qr-part="scanner.modal"]');
    await expect(scanner).toBeVisible();

    await page.keyboard.press('Escape');

    await expect(scanner).toBeHidden();
    await expect(actionModal).toBeVisible();
    await expect(actionModal.locator('[data-qr-part="scanner.camera-button"]')).toBeFocused();
});
