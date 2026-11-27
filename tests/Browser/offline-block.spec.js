import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const field = (page, name) => page.locator(`[data-field-name="${name}"]`).first();

const typeBurst = async (page, name, value) => {
    const input = field(page, name).locator('[data-qr-part="scanner.input"]');
    await input.focus();
    await page.keyboard.type(value, { delay: 8 });
    await page.keyboard.press('Enter');

    return input;
};

test('offline banner appears and blockWhenOffline rejects scans with a reason', async ({ page, context }) => {
    await page.goto('/workbench/offline-smoke');
    await page.waitForLoadState('networkidle');

    await expect(field(page, 'banner').locator('[data-qr-part="offline.banner"]')).toBeHidden();

    await context.setOffline(true);

    const bannerNotice = field(page, 'banner').locator('[data-qr-part="offline.banner"]');
    const blockedNotice = field(page, 'blocked').locator('[data-qr-part="offline.banner"]');
    await expect(bannerNotice).toBeVisible();
    await expect(blockedNotice).toBeVisible();
    await expect(bannerNotice).toContainText('This page is offline');

    const bannerInput = await typeBurst(page, 'banner', 'BANNER-1');
    await expect(bannerInput).toHaveValue('BANNER-1');
    await expect(page.getByTestId('state')).toContainText('"banner":"BANNER-1"');

    const blockedInput = await typeBurst(page, 'blocked', 'BLOCKED-1');
    await expect(blockedInput).not.toHaveValue('BLOCKED-1');
    const reasons = field(page, 'blocked').locator('[data-qr-part="scanner.reasons"]');
    await expect(reasons).toBeVisible();
    await expect(reasons).toContainText('Scanning is blocked while this page is offline.');
    await expect(page.getByTestId('state')).not.toContainText('BLOCKED-1');

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
