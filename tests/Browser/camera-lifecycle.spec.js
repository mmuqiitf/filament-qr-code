import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');

// Real cameras take a second or two to open; the instant fake hides the race.
const slowCameraAndTrackStreams = () => {
    window.__streams = [];
    const getUserMedia = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = async (constraints) => {
        await new Promise((resolve) => setTimeout(resolve, 1500));
        const stream = await getUserMedia(constraints);
        window.__streams.push(stream);

        return stream;
    };
};

const liveTracks = (page) => page.evaluate(
    () => window.__streams.flatMap((stream) => stream.getTracks()).filter((track) => track.readyState === 'live').length,
);

const prepare = async (page) => {
    await page.addInitScript(installFakeCamera, { svg });
    await page.addInitScript(slowCameraAndTrackStreams);
};

for (const closeAfterMs of [300, 2000]) {
    test(`scanner modal closed ${closeAfterMs} ms into camera start releases the camera`, async ({ page }) => {
        await prepare(page);
        await page.goto('/workbench/scan-smoke');
        await page.locator('[data-field-name="camera"] [data-qr-part="scanner.camera-button"]').click();
        await page.waitForTimeout(closeAfterMs);
        await page.keyboard.press('Escape');

        await page.waitForTimeout(5000);
        expect(await liveTracks(page)).toBe(0);
    });
}

test('collect action modal closed mid camera start releases the camera', async ({ page }) => {
    await prepare(page);
    await page.goto('/workbench/collect-smoke');
    await page.getByRole('button', { name: /batch scan/i }).click();
    await page.locator('.fi-modal-window .filament-qr-viewfinder').waitFor();
    await page.waitForTimeout(2000);
    await page.keyboard.press('Escape');

    await page.waitForTimeout(5000);
    expect(await liveTracks(page)).toBe(0);
});
