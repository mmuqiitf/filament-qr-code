<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

dataset('scan surfaces', [
    'scanner' => ['scanner', fn () => QrScanner::make('sku')
        ->nextField('employee')
        ->formats([BarcodeFormat::QrCode])
        ->fps(10)],
    'sequence' => ['sequence', fn () => QrScanSequence::make(['step', 'employee' => 'Employee badge'])],
    'collector' => ['collector', fn () => QrCollector::make('codes')
        ->allowDuplicates()
        ->delayBetweenScans(900)],
    'listener' => ['listener', fn () => QrHardwareScannerListener::make(['sku'])
        ->autoFocusNext(false)
        ->sound(false)],
    'collect action' => ['collect-action', fn () => QrCollectAction::make()],
]);

// Plain JSON fixtures instead of toMatchSnapshot(): Pest 3 and 4 name snapshot files differently.
it('keeps every scan surface payload stable', function (string $fixture, object $component) {
    $payload = $component->getScanPayload();
    ksort($payload);

    $expected = file_get_contents(__DIR__."/../Fixtures/payloads/{$fixture}.json");

    expect(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))->toBe($expected);
})->with('scan surfaces');
