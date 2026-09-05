<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

it('can configure a QrScanner field with sequential chaining', function () {
    $field = QrScanner::make('step')
        ->nextField('employee')
        ->sound(true)
        ->vibrate(true)
        ->hardwareScanner(enabled: true, burstThresholdMs: 40);

    expect($field->getName())->toBe('step')
        ->and($field->getNextField())->toBe('employee')
        ->and($field->hasSound())->toBeTrue()
        ->and($field->hasVibration())->toBeTrue()
        ->and($field->isHardwareScannerEnabled())->toBeTrue()
        ->and($field->getBurstThresholdMs())->toBe(40);
});

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
