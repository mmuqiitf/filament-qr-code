import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { installFakeCamera } from './fake-camera.js';

const qrSvgPath = new URL('./fixtures/camera-qr.svg', import.meta.url).pathname;
const svg = readFileSync(qrSvgPath, 'utf8');

const field = (page, name) => page.locator(`[data-field-name="${name}"]`).first();

// A hardware scanner types a whole code in a few milliseconds, then presses Enter.
const wedge = async (page, code) => {
    await page.keyboard.type(code, { delay: 8 });
    await page.keyboard.press('Enter');
};

const trackStreams = () => {
    window.__streams = [];
    const getUserMedia = navigator.mediaDevices.getUserMedia.bind(navigator.mediaDevices);
    navigator.mediaDevices.getUserMedia = async (constraints) => {
        const stream = await getUserMedia(constraints);
        window.__streams.push(stream);

        return stream;
    };
};

const liveTracks = (page) => page.evaluate(
    () => window.__streams.flatMap((stream) => stream.getTracks()).filter((track) => track.readyState === 'live').length,
);

test.describe('QrScanner', () => {
    test('a wedge scan that fails validation is rejected, then a valid scan is accepted', async ({ page }) => {
        await page.goto('/workbench/scan-smoke');
        const wrapper = field(page, 'wedge');
        const input = wrapper.locator('[data-qr-part="scanner.input"]');
        await page.evaluate(() => {
            window.__rejected = [];
            window.addEventListener('qr-scan-rejected', (event) => window.__rejected.push(event.detail.value));
        });
        await input.focus();

        await wedge(page, 'NOPE-1');

        await expect.poll(() => page.evaluate(() => window.__rejected)).toEqual(['NOPE-1']);
        await expect(page.getByTestId('state')).not.toContainText('NOPE-1');

        await input.fill('');
        await wedge(page, 'WEDGE-0002');

        await expect(page.getByTestId('state')).toContainText('"wedge":"WEDGE-0002"');
        await expect(wrapper).toHaveClass(/fi-qr-flash-success/);
    });

    test('camera scan: open, decode, fill, close and release the camera', async ({ page }) => {
        await page.addInitScript(installFakeCamera, { svg });
        await page.addInitScript(trackStreams);
        await page.goto('/workbench/scan-smoke');
        const wrapper = field(page, 'camera');

        await wrapper.locator('[data-qr-part="scanner.camera-button"]').click();
        await expect(wrapper.locator('[data-qr-part="scanner.modal"]')).toBeVisible();

        await expect(wrapper.locator('[data-qr-part="scanner.input"]')).toHaveValue('CAM-SMOKE-42', { timeout: 15_000 });
        await expect(wrapper.locator('[data-qr-part="scanner.modal"]')).toBeHidden();
        await expect(page.getByTestId('state')).toContainText('"camera":"CAM-SMOKE-42"');
        await expect.poll(() => liveTracks(page)).toBe(0);
    });

    test('scan from image decodes the uploaded file into the field', async ({ page }) => {
        // A blank camera: only the uploaded image carries a code.
        await page.addInitScript(installFakeCamera, { svg: '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>' });
        await page.goto('/workbench/scan-smoke');
        const wrapper = field(page, 'camera');

        await wrapper.locator('[data-qr-part="scanner.camera-button"]').click();
        await wrapper.locator('[data-qr-part="scanner.modal-actions"] input[type="file"]').setInputFiles(qrSvgPath);

        await expect(wrapper.locator('[data-qr-part="scanner.input"]')).toHaveValue('CAM-SMOKE-42', { timeout: 15_000 });
        await expect(page.getByTestId('state')).toContainText('"camera":"CAM-SMOKE-42"');
    });
});

test.describe('QrCollector announcements', () => {
    test('announces each added code and a rejected duplicate', async ({ page }) => {
        await page.goto('/workbench/collector-smoke');
        const collector = page.locator('[x-data^="qrCollector"]');
        const announcer = collector.locator('[data-qr-part="announcer"]');
        await expect(announcer).toHaveAttribute('aria-live', 'polite');

        const handled = page.getByTestId('handled');
        // The collector ignores scans for delayBetweenScans (1200 ms by default) after each accepted one.
        const pastScanDelay = () => page.waitForTimeout(1300);

        await wedge(page, 'BOX-1');
        await expect(announcer).toHaveText('Added BOX-1 (1 items)');
        await expect(handled).toHaveText('["BOX-1"]');
        await pastScanDelay();

        await wedge(page, 'BOX-2');
        await expect(announcer).toHaveText('Added BOX-2 (2 items)');
        await expect(handled).toHaveText('["BOX-1","BOX-2"]');
        await pastScanDelay();

        await wedge(page, 'BOX-1');
        await expect(announcer).toHaveText('Rejected: Duplicate codes are not allowed.');
        await expect(page.getByTestId('handled')).toHaveText('["BOX-1","BOX-2"]');
    });

    test('announces a camera scan', async ({ page }) => {
        await page.addInitScript(installFakeCamera, { svg });
        await page.goto('/workbench/collector-smoke');
        const collector = page.locator('[x-data^="qrCollector"]');

        await collector.getByRole('button', { name: /start scanning/i }).click();

        await expect(collector.locator('[data-qr-part="announcer"]')).toHaveText('Added CAM-SMOKE-42 (1 items)', { timeout: 15_000 });
    });
});

test.describe('QrScanSequence announcements', () => {
    test('announces a rejected step, each accepted step and completion', async ({ page }) => {
        await page.goto('/workbench/sequence-smoke');
        const announcer = page.locator('[data-qr-part="announcer"]');
        await expect(announcer).toHaveAttribute('aria-live', 'polite');

        await wedge(page, 'NOT-A-PO');
        await expect(announcer).toHaveText(/^Rejected: /);

        await wedge(page, 'PO-7');
        await expect(announcer).toHaveText('Added PO-7 to Purchase order');

        await wedge(page, 'SKU-9');
        await expect(announcer).toHaveText('Added SKU-9 to SKU. Sequence complete.');
        await expect(page.getByTestId('state')).toContainText('"po":"PO-7"');
        await expect(page.getByTestId('state')).toContainText('"sku":"SKU-9"');
    });
});
