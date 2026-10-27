import { expect, test } from '@playwright/test';

test('QrColumn thumbnail opens the preview instead of following the row link', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/workbench/table-smoke');

    await page.getByRole('img', { name: 'QR Code', exact: true }).click();

    const dialog = page.getByRole('dialog', { name: 'QR Code Preview' });
    await expect(dialog).toBeVisible();
    await expect(page).toHaveURL(/\/workbench\/table-smoke/);
    await expect(dialog.getByRole('link', { name: /download image/i })).toHaveAttribute('download', 'qrcode.svg');
    expect(await page.locator('a a').count()).toBe(0);

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();

    await page.getByText('Unit A').click();
    await expect(page).toHaveURL(/collect-smoke\?record=1/);
    expect(errors).toEqual([]);
});
