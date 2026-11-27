<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

it('supports custom scan format callbacks', function () {
    $field = QrScanner::make('sku')
        ->scanFormat(fn ($rawValue) => strtoupper(trim((string) $rawValue)));

    expect($field->formatScannedValue('  item-1234  '))->toBe('ITEM-1234');
});

it('can trigger onScan callback', function () {
    $called = false;
    $scanned = '';

    $field = QrScanner::make('code')
        ->onScan(function ($scannedValue) use (&$called, &$scanned) {
            $called = true;
            $scanned = $scannedValue;
        });

    $field->triggerOnScan('SCANNED_CODE_XYZ');

    expect($called)->toBeTrue()
        ->and($scanned)->toBe('SCANNED_CODE_XYZ');
});
