<?php

declare(strict_types=1);

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
        ->and($payload['suppressWhenGlobalListenerActive'])->toBeTrue()
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
        ->and($sequence['statePrefix'])->toBe('data')
        ->and($sequence['editable'])->toBeTrue()
        ->and($sequence['sound'])->toBeTrue();

    $collector = QrCollector::make('codes')->getScanPayload();

    expect($collector['allowDuplicates'])->toBeFalse()
        ->and($collector['delayBetweenScansMs'])->toBe(1200)
        ->and($collector['burstThresholdMs'])->toBe(50);

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

    $field = QrScanner::make('sku')->fps(15)->sound(true);

    expect($field->getFps())->toBe(15)
        ->and($field->hasSound())->toBeTrue()
        ->and($field->getScanPayload()['fps'])->toBe(15)
        ->and($field->getScanPayload()['sound'])->toBeTrue();
});
