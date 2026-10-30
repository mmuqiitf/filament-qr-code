import { readFileSync } from 'node:fs';
import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');
const blank = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>';

// WCAG 2.1 A/AA rules plus axe best practices, scoped to the package's own UI
// so Filament's panel chrome doesn't decide the result.
const axe = (page, selector) => new AxeBuilder({ page })
    .include(selector)
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'best-practice'])
    .analyze();

const violations = (results) => results.violations.map((violation) => ({
    id: violation.id,
    impact: violation.impact,
    targets: violation.nodes.map((node) => node.target.join(' ')),
}));

for (const path of ['scan-smoke', 'collector-smoke', 'collect-smoke', 'modal-smoke', 'table-smoke']) {
    test(`/workbench/${path} has no axe violations`, async ({ page }) => {
        await page.addInitScript(installFakeCamera, { svg: blank });
        await page.goto(`/workbench/${path}`);
        await page.waitForLoadState('networkidle');

        expect(violations(await axe(page, '.fi-main'))).toEqual([]);
    });
}

test('open scanner modal has no axe violations', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg: blank });
    await page.goto('/workbench/scan-smoke');
    const field = page.locator('[data-field-name="camera"]');
    await field.locator('[data-qr-part="scanner.camera-button"]').click();
    await expect(field.locator('[data-qr-part="scanner.modal"] video')).toBeVisible({ timeout: 10_000 });

    expect(violations(await axe(page, '[data-field-name="camera"] [data-qr-part="scanner.modal"]'))).toEqual([]);
});

test('scanner modal with a denied camera has no axe violations', async ({ page }) => {
    await page.addInitScript(() => {
        navigator.mediaDevices.getUserMedia = async () => {
            throw new DOMException('Permission denied', 'NotAllowedError');
        };
        navigator.mediaDevices.enumerateDevices = async () => [];
    });
    await page.goto('/workbench/scan-smoke');
    const field = page.locator('[data-field-name="camera"]');
    await field.locator('[data-qr-part="scanner.camera-button"]').click();
    await expect(field.locator('[role="alert"]').first()).toBeVisible({ timeout: 10_000 });

    expect(violations(await axe(page, '[data-field-name="camera"]'))).toEqual([]);
});

test('collect action modal with a collected code has no axe violations', async ({ page }) => {
    await page.addInitScript(installFakeCamera, { svg });
    await page.goto('/workbench/collect-smoke');
    await page.getByRole('button', { name: /batch scan/i }).click();
    const modal = page.locator('.fi-modal-window').filter({ has: page.locator('.filament-qr-viewfinder') });
    await expect(modal.locator('[data-qr-part="announcer"]')).toHaveText(/CAM-SMOKE-42/, { timeout: 15_000 });

    expect(violations(await axe(page, '.fi-modal-window'))).toEqual([]);
});
