import { expect, test } from '@playwright/test';

test('collector supports quantity rescan increment, inline edit, maxItems cap, CSV import report, and CSV export', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('response', (response) => response.status() >= 500 && errors.push(`${response.status()} ${response.url()}`));

    await page.goto('/workbench/collector-qty-csv-smoke');
    await page.waitForLoadState('networkidle');

    const collector = page.locator('[x-data^="qrCollector"]');
    const countBadge = collector.locator('[data-qr-part="collector.header"]');

    await expect(countBadge).toContainText('1 / 3');
    await expect(countBadge).toContainText('2 total');

    // Rescan PRD-100 via hardware wedge -> increments qty from 2 to 3.
    await page.keyboard.type('prd-100', { delay: 8 });
    await page.keyboard.press('Enter');

    await expect(countBadge).toContainText('1 / 3');
    await expect(countBadge).toContainText('3 total');
    await expect(page.getByTestId('collector-state')).toContainText('"qty":3');

    // Increment via '+' button -> qty 4.
    const qtyGroup = collector.locator('[data-qr-part="collector.qty"]').first();
    await qtyGroup.getByRole('button', { name: /increase quantity/i }).click();
    await expect(countBadge).toContainText('4 total');
    await expect(page.getByTestId('collector-state')).toContainText('"qty":4');

    // CSV import: merges PRD-100 (+1 => 5), adds PRD-200 (2) and PRD-300 (1), rejects BAD-1 (itemRules) and PRD-400 (maxItems=3).
    const csvContent = [
        'code,qty',
        'PRD-100,1',
        'PRD-200,2',
        'BAD-1,1',
        'PRD-300,1',
        'PRD-400,1',
    ].join('\n');

    await collector.locator('[data-qr-part="collector.csv-input"]').setInputFiles({
        name: 'import.csv',
        mimeType: 'text/csv',
        buffer: Buffer.from(csvContent, 'utf8'),
    });

    const report = collector.locator('[data-qr-part="collector.import-report"]');
    await expect(report).toBeVisible({ timeout: 10_000 });
    await expect(report).toContainText('Imported 3 row(s), 2 rejected.');
    await expect(report).toContainText('BAD-1');
    await expect(report).toContainText('PRD-400');

    await expect(countBadge).toContainText('3 / 3');
    await expect(countBadge).toContainText('8 total');

    // Export CSV triggers a download with code,qty header and rows.
    const [download] = await Promise.all([
        page.waitForEvent('download'),
        collector.locator('[data-qr-part="collector.export-csv"]').click(),
    ]);
    expect(download.suggestedFilename()).toBe('smoke-audit.csv');
    const stream = await download.createReadStream();
    const chunks = [];
    for await (const chunk of stream) {
        chunks.push(chunk);
    }
    const downloadedCsv = Buffer.concat(chunks).toString('utf8');
    expect(downloadedCsv).toContain('code,qty');
    expect(downloadedCsv).toContain('PRD-100,5');
    expect(downloadedCsv).toContain('PRD-200,2');
    expect(downloadedCsv).toContain('PRD-300,1');

    expect(errors).toEqual([]);
});
