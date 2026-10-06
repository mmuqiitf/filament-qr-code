<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
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

it('exposes hardware scanner, feedback, and camera defaults', function () {
    $field = QrScanner::make('sku')
        ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128]);

    expect($field->getFps())->toBe(25)
        ->and($field->getQrbox())->toBe(250)
        ->and($field->isPreferRearCamera())->toBeTrue()
        ->and($field->getSupportedFormats())->toBe(['QR_CODE', 'CODE_128'])
        ->and($field->getTerminators())->toBe(['Enter', 'Tab'])
        ->and($field->getMinBarcodeLength())->toBe(2)
        ->and($field->getBeepFrequencyHz())->toBe(880)
        ->and($field->getBeepDurationMs())->toBe(80)
        ->and($field->getVibrateDurationMs())->toBe(100);
});

it('supports custom feedback timing', function () {
    $field = QrScanner::make('sku')
        ->beepFrequency(660)
        ->beepDuration(120)
        ->vibrateDuration(200);

    expect($field->getBeepFrequencyHz())->toBe(660)
        ->and($field->getBeepDurationMs())->toBe(120)
        ->and($field->getVibrateDurationMs())->toBe(200);
});
