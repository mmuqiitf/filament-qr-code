<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Enums\ScannerPreset;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

it('exposes one scan payload with canonical keys for the scanner field', function () {
    $payload = QrScanner::make('sku')->nextField('employee')->getScanPayload();

    expect($payload['sound'])->toBeTrue()
        ->and($payload['vibrate'])->toBeTrue()
        ->and($payload['beepFrequency'])->toBe(880)
        ->and($payload['beepDurationMs'])->toBe(80)
        ->and($payload['vibrateDurationMs'])->toBe(100)
        ->and($payload['hardwareScanner'])->toBeTrue()
        ->and($payload['burstThresholdMs'])->toBe(50)
        ->and($payload['terminators'])->toBe(['Enter', 'Tab'])
        ->and($payload['minBarcodeLength'])->toBe(2)
        ->and($payload['scanTimeoutMs'])->toBe(150)
        ->and(array_key_exists('suppressWhenGlobalListenerActive', $payload))->toBeFalse()
        ->and($payload['qrbox'])->toBe(250)
        ->and($payload['preferRearCamera'])->toBeTrue()
        ->and($payload['formats'])->toBe([])
        ->and($payload['nextField'])->toBe('employee')
        ->and($payload['insecureMessage'])->toContain('https')
        ->and(array_key_exists('beepDuration', $payload))->toBeFalse();
});

it('exposes sequence fields and collector options through the same payload', function () {
    $sequence = QrScanSequence::make(['step', 'employee'])->getScanPayload();

    expect($sequence['fields'])->toBe([
        ['key' => 'step', 'label' => 'Step'],
        ['key' => 'employee', 'label' => 'Employee'],
    ])
        ->and($sequence['statePrefix'])->toBe('')
        ->and($sequence['editable'])->toBeTrue()
        ->and($sequence['sound'])->toBeTrue();

    expect(QrScanSequence::make(['step'])->editable(false)->getScanPayload()['editable'])->toBeFalse();

    $collector = QrCollector::make('codes')->getScanPayload();

    expect($collector['allowDuplicates'])->toBeFalse()
        ->and($collector['delayBetweenScansMs'])->toBe(1200)
        ->and($collector['burstThresholdMs'])->toBe(50);

    expect(QrCollector::make('codes')->delayBetweenScans(1500)->getScanPayload()['delayBetweenScansMs'])->toBe(1500);

    $listener = QrHardwareScannerListener::make(['sku'])->getScanPayload();

    expect($listener['fields'])->toBe(['sku'])
        ->and($listener['preventSubmit'])->toBeTrue()
        ->and($listener['autoFocusNext'])->toBeTrue()
        ->and($listener['sound'])->toBeTrue();

    $action = QrCollectAction::make()->getScanPayload();

    expect($action['allowDuplicates'])->toBeFalse()
        ->and($action['fps'])->toBe(12)
        ->and($action['beepDurationMs'])->toBe(80);
});

it('reads scan defaults from config when the developer did not set them', function () {
    config()->set('qr-code.camera.fps', 30);
    config()->set('qr-code.camera.qrbox', 300);
    config()->set('qr-code.camera.prefer_rear_camera', false);
    config()->set('qr-code.hardware_scanner.burst_threshold_ms', 75);
    config()->set('qr-code.hardware_scanner.default_terminators', ['Enter']);
    config()->set('qr-code.feedback.sound', false);
    config()->set('qr-code.feedback.beep_frequency', 660);

    $field = QrScanner::make('sku');

    expect($field->getFps())->toBe(30)
        ->and($field->getQrbox())->toBe(300)
        ->and($field->isPreferRearCamera())->toBeFalse()
        ->and($field->getBurstThresholdMs())->toBe(75)
        ->and($field->getTerminators())->toBe(['Enter'])
        ->and($field->hasSound())->toBeFalse()
        ->and($field->getBeepFrequencyHz())->toBe(660);

    $payload = $field->getScanPayload();

    expect($payload['fps'])->toBe($field->getEffectiveFps())
        ->and($payload['qrbox'])->toBe(300)
        ->and($payload['sound'])->toBeFalse()
        ->and($payload['beepFrequency'])->toBe(660);
});

it('prefers explicit setters over config for scan defaults', function () {
    config()->set('qr-code.camera.fps', 30);
    config()->set('qr-code.feedback.sound', false);

    $field = QrScanner::make('sku')
        ->fps(15)
        ->sound(true)
        ->beepFrequency(660)
        ->beepDuration(120)
        ->vibrateDuration(200);

    expect($field->getFps())->toBe(15)
        ->and($field->hasSound())->toBeTrue()
        ->and($field->getScanPayload())->toMatchArray([
            'fps' => 15,
            'sound' => true,
            'beepFrequency' => 660,
            'beepDurationMs' => 120,
            'vibrateDurationMs' => 200,
        ]);
});

it('exposes min fast keys from config with a default of three', function () {
    expect(QrScanner::make('sku')->getScanPayload()['minFastKeys'])->toBe(3);

    config()->set('qr-code.hardware_scanner.min_fast_keys', 1);

    expect(QrScanner::make('sku')->getScanPayload()['minFastKeys'])->toBe(1)
        ->and(QrScanner::make('sku')->hardwareScanner(minFastKeys: 4)->getScanPayload()['minFastKeys'])->toBe(4);
});

it('exposes the listener any-input fallback flag', function () {
    expect(QrHardwareScannerListener::make(['sku'])->getScanPayload()['fallbackToAnyInput'])->toBeTrue()
        ->and(QrHardwareScannerListener::make(['sku'])->fallbackToAnyInput(false)->getScanPayload()['fallbackToAnyInput'])->toBeFalse();

    config()->set('qr-code.hardware_scanner.fallback_to_any_input', false);

    expect(QrHardwareScannerListener::make(['sku'])->getScanPayload()['fallbackToAnyInput'])->toBeFalse();
});

it('merges component-contributed extras into the payload', function () {
    $scanner = new class('sku') extends QrScanner
    {
        public function getComponentScanPayload(): array
        {
            return ['stationId' => 'line-3'];
        }
    };

    expect($scanner->getScanPayload())
        ->toHaveKey('stationId', 'line-3')
        ->toHaveKey('burstThresholdMs', 50);
});

it('sends a translated message for images without a readable code', function () {
    app()->setLocale('id');

    $payload = QrScanner::make('sku')->getScanPayload();

    expect($payload['fileScanFailedMessage'])->toBe('Tidak ada barcode atau kode QR pada gambar yang dipilih.');
});

it('applies camera presets across every camera surface and lets explicit setters win in any order', function (object $field) {
    $configured = $field->preset(ScannerPreset::Warehouse);
    $payload = $configured->getScanPayload();

    expect($configured->getPreset())->toBe(ScannerPreset::Warehouse)
        ->and($payload['fps'])->toBe(20)
        ->and($payload['formats'])->toBe(['CODE_128', 'CODE_39', 'ITF', 'QR_CODE', 'DATA_MATRIX'])
        ->and($payload['scanRegion'])->toBe(['width' => 0.80, 'height' => 0.55])
        ->and($payload['preferRearCamera'])->toBeTrue()
        ->and($payload['cameraControls'])->toBeTrue();
})->with([
    'scanner' => fn () => QrScanner::make('sku'),
    'sequence' => fn () => QrScanSequence::make(['step']),
    'collector' => fn () => QrCollector::make('codes'),
    'collect action' => fn () => QrCollectAction::make(),
]);

it('lets explicit setters override preset values regardless of call order', function () {
    $after = QrScanner::make('sku')
        ->preset(ScannerPreset::Retail)
        ->formats([BarcodeFormat::QrCode])
        ->fps(15)
        ->scanRegion(320, 140)
        ->preferRearCamera(false)
        ->cameraControls(false);

    $before = QrScanner::make('sku')
        ->formats([BarcodeFormat::QrCode])
        ->fps(15)
        ->scanRegion(320, 140)
        ->preferRearCamera(false)
        ->cameraControls(false)
        ->preset(ScannerPreset::Retail);

    foreach ([$after, $before] as $field) {
        $payload = $field->getScanPayload();
        expect($payload['formats'])->toBe(['QR_CODE'])
            ->and($payload['fps'])->toBe(15)
            ->and($payload['scanRegion'])->toBe(['width' => 320, 'height' => 140])
            ->and($payload['preferRearCamera'])->toBeFalse()
            ->and($payload['cameraControls'])->toBeFalse();
    }

    $qrboxWins = QrScanner::make('sku')
        ->preset(ScannerPreset::Warehouse)
        ->qrbox(200);

    expect($qrboxWins->getQrbox())->toBe(200)
        ->and(array_key_exists('scanRegion', $qrboxWins->getScanPayload()))->toBeFalse();

    $clearedRegion = QrScanner::make('sku')
        ->preset(ScannerPreset::Warehouse)
        ->scanRegion(null, null);

    expect(array_key_exists('scanRegion', $clearedRegion->getScanPayload()))->toBeFalse();
});

it('reads preset and scanRegion defaults from config and accepts closures', function () {
    config()->set('qr-code.camera.preset', 'ticketing');

    $fromConfigPreset = QrScanner::make('sku')->getScanPayload();
    expect($fromConfigPreset['fps'])->toBe(30)
        ->and($fromConfigPreset['formats'])->toBe(['QR_CODE', 'AZTEC'])
        ->and($fromConfigPreset['scanRegion'])->toBe(['width' => 0.65, 'height' => 0.65]);

    config()->set('qr-code.camera.preset', null);
    config()->set('qr-code.camera.scan_region', ['width' => 0.9, 'height' => 0.4]);

    $fromConfigRegion = QrScanner::make('sku')->getScanPayload();
    expect($fromConfigRegion['scanRegion'])->toBe(['width' => 0.9, 'height' => 0.4]);

    $fromClosure = QrScanner::make('sku')
        ->preset(fn () => ScannerPreset::Gs1)
        ->scanRegion(fn () => 280, fn () => 120)
        ->getScanPayload();

    expect($fromClosure['formats'])->toBe(['CODE_128', 'DATA_MATRIX', 'QR_CODE'])
        ->and($fromClosure['scanRegion'])->toBe(['width' => 280, 'height' => 120]);
});

it('lets a configured preset replace the other camera defaults', function () {
    config()->set('qr-code.camera.preset', 'warehouse');
    config()->set('qr-code.camera.fps', 10);
    config()->set('qr-code.camera.scan_region', ['width' => 0.5, 'height' => 0.5]);
    config()->set('qr-code.camera.prefer_rear_camera', false);
    config()->set('qr-code.camera.controls', false);

    $payload = QrScanner::make('sku')->getScanPayload();

    expect($payload['fps'])->toBe(20)
        ->and($payload['scanRegion'])->toBe(['width' => 0.80, 'height' => 0.55])
        ->and($payload['preferRearCamera'])->toBeTrue()
        ->and($payload['cameraControls'])->toBeTrue()
        ->and($payload['formats'])->toBe(['CODE_128', 'CODE_39', 'ITF', 'QR_CODE', 'DATA_MATRIX']);

    $overridden = QrScanner::make('sku')
        ->fps(12)
        ->scanRegion(0.4, 0.2)
        ->preferRearCamera(false)
        ->cameraControls(false)
        ->getScanPayload();

    expect($overridden['fps'])->toBe(12)
        ->and($overridden['scanRegion'])->toBe(['width' => 0.4, 'height' => 0.2])
        ->and($overridden['preferRearCamera'])->toBeFalse()
        ->and($overridden['cameraControls'])->toBeFalse();
});

it('rejects invalid scanRegion bounds', function (mixed $width, mixed $height) {
    expect(fn () => QrScanner::make('sku')->scanRegion($width, $height)->getScanPayload())
        ->toThrow(InvalidArgumentException::class);
})->with([
    'zero width' => [0, 0.5],
    'negative height' => [0.8, -1],
    'float ratio over 1' => [1.25, 0.5],
    'single axis null' => [0.8, null],
]);
