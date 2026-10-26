import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

// A blank frame: a decoded code would close the scanner before it is measured.
const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480"><rect width="640" height="480" fill="#fff"/></svg>';

// A transformed ancestor (an action modal or slide-over) would become the
// containing block of the position: fixed overlay. The scanner overlay must
// cover the whole viewport, also on a panel whose theme doesn't scan the
// package views (this workbench has no theme).
for (const action of ['Scan in modal', 'Scan in slide-over']) {
    test(`QrScanner modal opened from "${action}" covers the viewport`, async ({ page }) => {
        await page.addInitScript(installFakeCamera, { svg });
        await page.goto('/workbench/modal-smoke');
        await page.getByRole('button', { name: action }).click();
        await page.locator('.fi-modal-window [data-qr-part="scanner.camera-button"]').click();

        const modal = page.locator('[data-qr-part="scanner.modal"]');
        await expect(modal).toBeVisible();
        await page.waitForTimeout(500);

        const box = await modal.boundingBox();
        const viewport = page.viewportSize();
        expect(box).toEqual({ x: 0, y: 0, width: viewport.width, height: viewport.height });
    });
}
