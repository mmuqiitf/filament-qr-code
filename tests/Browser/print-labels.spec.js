import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('print labels action opens a sheet of labels with no axe violations', async ({ page }) => {
    await page.goto('/workbench/labels-smoke');
    await page.waitForLoadState('networkidle');

    const popupPromise = page.waitForEvent('popup');
    await page.getByRole('button', { name: /print labels/i }).click();
    const sheet = await popupPromise;

    await expect(sheet.locator('.filament-qr-label-sheet')).toBeVisible();
    await expect(sheet.locator('.filament-qr-label').first()).toBeVisible();
    await expect(sheet.locator('.filament-qr-label__caption')).toHaveText('Unit A');
    const qr = sheet.locator('.filament-qr-label__qr');
    await expect(qr).toBeVisible();
    await expect.poll(async () => qr.evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);

    const axeResults = await new AxeBuilder({ page: sheet })
        .include('.filament-qr-label-sheet')
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice'])
        .analyze();

    expect(axeResults.violations).toEqual([]);
});
