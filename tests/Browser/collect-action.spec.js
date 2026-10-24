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
    await page.addInitScript(installFakeCamera, { svg });

    const modal = await openCollectModal(page);

    await expect(modal.locator('.filament-qr-viewfinder video')).toBeVisible({ timeout: 10_000 });
    await expect(modal).toContainText('CAM-SMOKE-42', { timeout: 15_000 });
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

    await expect(status).toContainText('Camera access unavailable', { timeout: 10_000 });
    await expect(status.getByRole('button', { name: /retry/i })).toBeVisible();
});
