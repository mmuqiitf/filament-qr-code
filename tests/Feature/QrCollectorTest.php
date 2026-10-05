<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

it('configures QrCollector properly', function () {
    $collector = QrCollector::make('items')
        ->allowDuplicates(false)
        ->delayBetweenScans(1500)
        ->fps(25)
        ->qrbox(200);

    expect($collector->getName())->toBe('items')
        ->and($collector->isDuplicatesAllowed())->toBeFalse()
        ->and($collector->getDelayBetweenScansMs())->toBe(1500)
        ->and($collector->getFps())->toBe(25)
        ->and($collector->getQrbox())->toBe(200);
});

it('filters symbologies and prefers the rear camera by default', function () {
    $collector = QrCollector::make('items')
        ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128]);

    expect($collector->getSupportedFormats())->toBe(['QR_CODE', 'CODE_128'])
        ->and($collector->isPreferRearCamera())->toBeTrue();
});
