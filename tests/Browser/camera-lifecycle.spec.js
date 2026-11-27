import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const svg = readFileSync(new URL('./fixtures/camera-qr.svg', import.meta.url), 'utf8');

// Real cameras take a second or two to open; the instant fake hides the race.
const slowCameraAndTrackStreams = () => {
    window.__streams = [];
    window.__cameraRequests = 0;
    window.__pendingCameraRequests = 0;
    const getUserMedia = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = async (constraints) => {
        window.__cameraRequests++;
        window.__pendingCameraRequests++;
        try {
            await new Promise((resolve) => setTimeout(resolve, 400));
            const stream = await getUserMedia(constraints);
            window.__streams.push(stream);

            return stream;
        } finally {
            window.__pendingCameraRequests--;
        }
    };
};

const cameraRequests = (page) => page.evaluate(() => window.__cameraRequests);
const pendingCameraRequests = (page) => page.evaluate(() => window.__pendingCameraRequests);
const streamCount = (page) => page.evaluate(() => window.__streams.length);
const liveTracks = (page) => page.evaluate(
    () => window.__streams.flatMap((stream) => stream.getTracks()).filter((track) => track.readyState === 'live').length,
);

const prepare = async (page) => {
    await page.addInitScript(installFakeCamera, { svg });
    await page.addInitScript(slowCameraAndTrackStreams);
};

test('scanner modal closed before camera stream resolves releases the camera', async ({ page }) => {
    await prepare(page);
    await page.goto('/workbench/scan-smoke');
    await page.locator('[data-field-name="camera"] [data-qr-part="scanner.camera-button"]').click();
    await expect.poll(() => cameraRequests(page)).toBeGreaterThan(0);
    await page.keyboard.press('Escape');

    await expect.poll(() => pendingCameraRequests(page), { timeout: 6_000 }).toBe(0);
    await expect.poll(() => streamCount(page), { timeout: 6_000 }).toBeGreaterThan(0);
    await expect.poll(() => liveTracks(page), { timeout: 6_000 }).toBe(0);
});

test('scanner modal closed right as camera stream resolves releases the camera', async ({ page }) => {
    await prepare(page);
    await page.goto('/workbench/scan-smoke');
    await page.locator('[data-field-name="camera"] [data-qr-part="scanner.camera-button"]').click();
    await expect.poll(() => streamCount(page), { timeout: 6_000 }).toBeGreaterThan(0);
    await page.keyboard.press('Escape');

    await expect.poll(() => liveTracks(page), { timeout: 6_000 }).toBe(0);
});

test('collect action modal closed mid camera start releases the camera', async ({ page }) => {
    await prepare(page);
    await page.goto('/workbench/collect-smoke');
    await page.getByRole('button', { name: /batch scan/i }).click();
    await page.locator('.fi-modal-window .filament-qr-viewfinder').waitFor();
    await expect.poll(() => cameraRequests(page)).toBeGreaterThan(0);
    await page.keyboard.press('Escape');

    await expect.poll(() => pendingCameraRequests(page), { timeout: 6_000 }).toBe(0);
    await expect.poll(() => streamCount(page), { timeout: 6_000 }).toBeGreaterThan(0);
    await expect.poll(() => liveTracks(page), { timeout: 6_000 }).toBe(0);
});

for (const path of ['sequence-smoke', 'collector-smoke']) {
    test(`/workbench/${path} does not ask for the camera before Start`, async ({ page }) => {
        await page.addInitScript(() => {
            window.__cameraRequests = 0;
            const original = navigator.mediaDevices?.getUserMedia?.bind(navigator.mediaDevices);
            if (original) {
                navigator.mediaDevices.getUserMedia = (...args) => {
                    window.__cameraRequests++;

                    return original(...args);
                };
            }
        });
        await page.goto(`/workbench/${path}`);
        await page.waitForLoadState('networkidle');

        expect(await page.evaluate(() => window.__cameraRequests)).toBe(0);
        await expect(page.getByText('Camera access denied or unavailable.')).toBeHidden();
    });
}
