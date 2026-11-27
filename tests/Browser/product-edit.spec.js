import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('product edit live barcode updates when the SKU is typed', async ({ page }) => {
    await page.goto('/workbench/product-edit');
    await page.waitForLoadState('networkidle');

    const initial = page.getByRole('img', { name: 'Code 128 barcode: SKU-100', exact: true });
    await expect(initial).toBeVisible();

    await page.getByLabel('SKU').fill('SKU-999');

    const updated = page.getByRole('img', { name: 'Code 128 barcode: SKU-999', exact: true });
    await expect(updated).toBeVisible();
    await expect(initial).toHaveCount(0);

    const axeResults = await new AxeBuilder({ page })
        .include('.fi-main')
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice'])
        .analyze();

    expect(axeResults.violations.map((violation) => ({
        id: violation.id,
        impact: violation.impact,
        targets: violation.nodes.map((node) => node.target.join(' ')),
    }))).toEqual([]);
});
