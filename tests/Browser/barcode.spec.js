import { expect, test } from '@playwright/test';

test('barcode components render every type with alt text and an inline error', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/workbench/barcode-smoke');

    for (const name of [
        'Code 128 barcode: SKU-128-A',
        'EAN-13 barcode: 4006381333931',
        'EAN-8 barcode: 96385074',
        'UPC-A barcode: 036000291452',
        'Code 39 barcode: HELLO-1',
        'ITF-14 barcode: 00012345678905',
        'Code 128 barcode: ENTRY-1',
    ]) {
        const image = page.getByRole('img', { name, exact: true });
        await expect(image).toBeVisible();
        expect(await image.evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);
    }

    await expect(page.getByRole('alert').filter({ hasText: 'wrong check digit, expected 1' })).toBeVisible();
    await expect(page.locator('a[download="barcode.png"]')).toHaveCount(1);
    expect(errors).toEqual([]);
});

test('BarcodeColumn renders a compact barcode in the table', async ({ page }) => {
    await page.goto('/workbench/table-smoke');

    const image = page.getByRole('img', { name: 'Code 128 barcode: SKU-TABLE-1', exact: true });
    await expect(image).toBeVisible();
    expect(await image.evaluate((img) => img.naturalHeight)).toBe(32);
});
