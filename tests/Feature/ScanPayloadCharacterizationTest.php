<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

dataset('scan surfaces', [
    'scanner' => fn () => QrScanner::make('sku')
        ->nextField('employee')
        ->formats([BarcodeFormat::QrCode])
        ->fps(10),
    'sequence' => fn () => QrScanSequence::make(['step', 'employee' => 'Employee badge']),
    'collector' => fn () => QrCollector::make('codes')
        ->allowDuplicates()
        ->delayBetweenScans(900),
    'listener' => fn () => QrHardwareScannerListener::make(['sku'])
        ->autoFocusNext(false)
        ->sound(false),
    'collect action' => fn () => QrCollectAction::make(),
]);

it('keeps every scan surface payload stable', function (object $component) {
    $payload = $component->getScanPayload();
    ksort($payload);

    expect($payload)->toMatchSnapshot();
})->with('scan surfaces');
