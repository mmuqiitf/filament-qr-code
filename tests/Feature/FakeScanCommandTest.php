<?php

declare(strict_types=1);

it('accepts a GS1 label and prints the bracketed string', function (): void {
    $this->artisan('qr-code:fake-scan', [
        'code' => '(01)09506000134352(17)261231(10)ABC',
        '--gs1' => true,
    ])
        ->expectsOutputToContain('Accepted: (01)09506000134352(17)261231(10)ABC')
        ->assertSuccessful();
});

it('rejects a plain barcode in GS1 mode with the scan reason', function (): void {
    $this->artisan('qr-code:fake-scan', [
        'code' => '4006381333931',
        '--gs1' => true,
    ])
        ->expectsOutputToContain('Rejected: Plain product barcode, not a GS1 label')
        ->assertFailed();
});

it('rejects an empty code', function (): void {
    $this->artisan('qr-code:fake-scan', ['code' => '   '])
        ->expectsOutputToContain('The scanned code is empty.')
        ->assertFailed();
});

it('rejects a GS1 label that is missing a required AI', function (): void {
    $this->artisan('qr-code:fake-scan', [
        'code' => '(01)09506000134352',
        '--gs1' => true,
        '--require' => ['17'],
    ])
        ->expectsOutputToContain('Rejected: Missing AI (17)')
        ->assertFailed();
});
