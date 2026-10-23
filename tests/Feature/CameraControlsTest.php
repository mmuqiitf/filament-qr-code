<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

it('shows camera controls by default on every camera surface', function (object $field) {
    expect($field->getScanPayload()['cameraControls'])->toBeTrue();
})->with([
    'scanner' => fn () => QrScanner::make('sku'),
    'sequence' => fn () => QrScanSequence::make(['step']),
    'collector' => fn () => QrCollector::make('codes'),
    'collect action' => fn () => QrCollectAction::make(),
]);

it('hides camera controls per field', function (object $field) {
    expect($field->cameraControls(false)->getScanPayload()['cameraControls'])->toBeFalse();
})->with([
    'scanner' => fn () => QrScanner::make('sku'),
    'sequence' => fn () => QrScanSequence::make(['step']),
    'collector' => fn () => QrCollector::make('codes'),
    'collect action' => fn () => QrCollectAction::make(),
]);

it('reads the camera controls default from config and accepts closures', function () {
    config()->set('qr-code.camera.controls', false);

    expect(QrScanner::make('sku')->hasCameraControls())->toBeFalse()
        ->and(QrScanner::make('sku')->cameraControls(fn () => true)->hasCameraControls())->toBeTrue();
});

it('renders the torch and zoom controls in the scanner viewfinder', function () {
    $html = view('filament-qr-code::components.camera-controls')->render();

    expect($html)
        ->toContain('data-qr-part="camera.controls"')
        ->toContain('toggleTorch()')
        ->toContain('setZoom(');
});
