<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrWedgeListener;

it('supports multi-format barcode configurations in QrScanner', function () {
    $field = QrScanner::make('barcode')
        ->formats([
            BarcodeFormat::QrCode,
            BarcodeFormat::Code128,
            BarcodeFormat::Code39,
            BarcodeFormat::Ean13,
        ]);

    expect($field->getSupportedFormats())
        ->toBeArray()
        ->toContain('QR_CODE')
        ->toContain('CODE_128')
        ->toContain('CODE_39')
        ->toContain('EAN_13');
});

it('configures QrWedgeListener for hands-free warehouse scanning', function () {
    $listener = QrWedgeListener::make([
        'step',
        'employee',
        'serial',
    ])
        ->autoFocusNext(true)
        ->sound(true)
        ->vibrate(true)
        ->hardwareScanner(enabled: true, burstThresholdMs: 45);

    expect($listener->getFields())->toBe(['step', 'employee', 'serial'])
        ->and($listener->isAutoFocusNext())->toBeTrue()
        ->and($listener->hasSound())->toBeTrue()
        ->and($listener->hasVibration())->toBeTrue()
        ->and($listener->isHardwareScannerEnabled())->toBeTrue()
        ->and($listener->getBurstThresholdMs())->toBe(45);
});
