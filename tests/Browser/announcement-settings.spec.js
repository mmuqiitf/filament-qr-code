import { expect, test } from '@playwright/test';

const field = (page, name) => page.locator(`[data-field-name="${name}"]`).first();

const wedge = async (page, code, delay = 8) => {
    await page.keyboard.type(code, { delay });
    await page.keyboard.press('Enter');
};

test('announceUsing replaces the text of accepted scans and falls back on null', async ({ page }) => {
    await page.goto('/workbench/announce-smoke');
    const wrapper = field(page, 'custom');
    const announcer = wrapper.locator('[data-qr-part="announcer"]');

    await wrapper.locator('[data-qr-part="scanner.input"]').focus();
    await wedge(page, 'OK-1');
    await expect(announcer).toHaveText('Checked in OK-1 at Custom');

    await wrapper.locator('[data-qr-part="scanner.input"]').focus();
    await wedge(page, 'NO-1');
    await expect(announcer).toHaveText(/^Rejected: .*OK-/);
});

test('announcements(false) leaves the live region empty', async ({ page }) => {
    await page.goto('/workbench/announce-smoke');
    const wrapper = field(page, 'quiet');

    await wrapper.locator('[data-qr-part="scanner.input"]').focus();
    await wedge(page, 'QUIET-1');

    await expect(page.getByTestId('state')).toContainText('QUIET-1');
    await expect(wrapper.locator('[data-qr-part="announcer"]')).toHaveText('');
});

test('rapid collector scans are coalesced so only the latest is read', async ({ page }) => {
    await page.goto('/workbench/rapid-collector-smoke');
    const wrapper = page.locator('[x-data^="qrCollector"]');
    const announcer = wrapper.locator('[data-qr-part="announcer"]');
    // Wait for Alpine to boot the collector, or the first wedge scan has no listener yet.
    await page.waitForFunction(() => document.querySelector('[x-data^="qrCollector"]')?._x_dataStack !== undefined);

    await announcer.evaluate((element) => {
        window.__announced = [];
        new MutationObserver(() => {
            if (element.textContent) window.__announced.push(element.textContent);
        }).observe(element, { childList: true, characterData: true, subtree: true });
    });

    for (const code of ['RAPID-1', 'RAPID-2', 'RAPID-3']) {
        await wedge(page, code);
    }

    await expect(announcer).toHaveText('Added RAPID-3 (3 items)');
    const announced = await page.evaluate(() => window.__announced);
    expect(announced).not.toContain('Added RAPID-2 (2 items)');
    expect(announced.length).toBeLessThan(3);
});
