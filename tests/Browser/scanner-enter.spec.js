import { expect, test } from '@playwright/test';

// Enter in a GS1 scanner field: a rejected label keeps its reason and does
// not submit; an accepted label submits right away.
const typeLabel = async (page, value) => {
    const input = page.locator('[data-field-name="label"] [data-qr-part="scanner.input"]');
    await input.fill(value);
    await input.press('Enter');
};

test.beforeEach(async ({ page }) => {
    await page.goto('/workbench/gs1-smoke');
    await page.waitForLoadState('networkidle');
});

for (const [label, reason] of [
    ['8901234567074', 'not a GS1 label'],
    ['(01)08901234567081(10)BAT-2611', 'Missing AI (17)'],
]) {
    test(`Enter on a rejected label (${label}) keeps the reason and does not submit`, async ({ page }) => {
        await typeLabel(page, label);

        await expect(page.getByText(reason)).toBeVisible();
        await page.waitForLoadState('networkidle');
        await expect(page.getByText(/field is required/)).toHaveCount(0);
        await expect(page.getByTestId('submitted')).toHaveText('0');
    });
}

test('Enter on an accepted label submits the form', async ({ page }) => {
    await typeLabel(page, '(01)08901234567081(17)301231(10)BAT-2611');

    await expect(page.getByTestId('submitted')).toHaveText('1');
});
