import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');

const openCollectModal = async (page) => {
    await page.goto('/workbench/collect-smoke');
    await page.getByRole('button', { name: /batch scan/i }).click();
    const modal = page.locator('.fi-modal-window').filter({ has: page.locator('.filament-qr-viewfinder') });
    await expect(modal).toBeVisible();

    return modal;
};

test('collect action modal starts the camera on open and collects codes (#35)', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('response', (response) => response.status() >= 500 && errors.push(`${response.status()} ${response.url()}`));
    await page.addInitScript(installFakeCamera, { svg });
    await page.addInitScript(() => {
        window.__collected = [];
        window.addEventListener('qr-collector-item-added', (event) => window.__collected.push(event.detail.code));
    });

    const modal = await openCollectModal(page);

    await expect(modal.locator('.filament-qr-viewfinder video')).toBeVisible({ timeout: 10_000 });
    await expect(modal).toContainText('CAM-SMOKE-42', { timeout: 15_000 });
    await expect.poll(() => page.evaluate(() => window.__collected)).toEqual(['CAM-SMOKE-42']);
    const announcer = modal.locator('[data-qr-part="announcer"]');
    await expect(announcer).toHaveAttribute('aria-live', 'polite');
    await expect(announcer).toHaveText('Added CAM-SMOKE-42 (1 items)');
    await page.waitForLoadState('networkidle');
    expect(errors).toEqual([]);
});

test('collect action modal explains a denied camera and offers retry (#35)', async ({ page }) => {
    await page.addInitScript(() => {
        navigator.mediaDevices.getUserMedia = async () => {
            throw new DOMException('Permission denied', 'NotAllowedError');
        };
        navigator.mediaDevices.enumerateDevices = async () => [];
    });

    const modal = await openCollectModal(page);
    const status = modal;

    await expect(status).toContainText('Camera access denied or unavailable.', { timeout: 10_000 });
    await expect(status.getByRole('button', { name: /retry/i })).toBeVisible();
});
