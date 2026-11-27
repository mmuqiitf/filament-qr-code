import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('station listener fills a form field from a keyboard-wedge burst', async ({ page }) => {
    await page.addInitScript(() => {
        window.__hardwareScanned = [];
        window.addEventListener('qr-hardware-scanned', (event) => {
            window.__hardwareScanned.push(event.detail);
        });
    });

    await page.goto('/workbench/listener-smoke');
    await page.waitForLoadState('networkidle');

    await page.getByRole('heading', { name: 'Listener smoke' }).click();
    await page.keyboard.type('SKU-WEDGE-1', { delay: 8 });
    await page.keyboard.press('Enter');

    const input = page.getByLabel('SKU');
    await expect(input).toHaveValue('SKU-WEDGE-1');
    await expect(page.getByTestId('state')).toContainText('"sku":"SKU-WEDGE-1"');
    await expect.poll(() => page.evaluate(() => window.__hardwareScanned.map((detail) => detail.value))).toEqual(['SKU-WEDGE-1']);

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
