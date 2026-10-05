<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

it('configures sequential scan fields properly', function () {
    $sequence = QrScanSequence::make([
        'step',
        'employee',
        'document',
        'mo_number',
        'equipment',
    ])->fps(20)->qrbox(300);

    $fields = $sequence->getScanFields();

    expect($fields)->toHaveCount(5)
        ->and($fields[0]['key'])->toBe('step')
        ->and($fields[0]['label'])->toBe('Step')
        ->and($fields[1]['key'])->toBe('employee')
        ->and($fields[2]['key'])->toBe('document')
        ->and($fields[3]['key'])->toBe('mo_number')
        ->and($fields[3]['label'])->toBe('Mo Number')
        ->and($fields[4]['key'])->toBe('equipment')
        ->and($sequence->getFps())->toBe(20)
        ->and($sequence->getQrbox())->toBe(300);
});

it('binds sequence scans to a configurable form state prefix', function () {
    $sequence = QrScanSequence::make(['step', 'employee'])
        ->statePathPrefix('order');

    expect($sequence->getStatePathPrefix())->toBe('order');
});

it('defaults the state prefix to the conventional form data path', function () {
    expect(QrScanSequence::make(['step'])->getStatePathPrefix())->toBe('data');
});

it('filters symbologies and prefers the rear camera by default', function () {
    $sequence = QrScanSequence::make(['step'])
        ->formats([BarcodeFormat::QrCode, BarcodeFormat::Ean13])
        ->preferRearCamera(false);

    expect($sequence->getSupportedFormats())->toBe(['QR_CODE', 'EAN_13'])
        ->and($sequence->isPreferRearCamera())->toBeFalse();
});

it('supports per-step scan formatting and step callbacks', function () {
    $seen = [];

    $sequence = QrScanSequence::make(['step'])
        ->scanFormat(fn ($rawValue) => strtoupper(trim((string) $rawValue)))
        ->onStepScanned(function ($field, $scannedValue) use (&$seen) {
            $seen = [$field, $scannedValue];
        });

    expect($sequence->formatScannedValue('  abc-123  '))->toBe('ABC-123');

    $sequence->triggerOnStep('step', 'ABC-123');

    expect($seen)->toBe(['step', 'ABC-123']);
});

it('allows inline correction of captured values by default', function () {
    expect(QrScanSequence::make(['step'])->isEditable())->toBeTrue();

    expect(QrScanSequence::make(['step'])->editable(false)->isEditable())->toBeFalse();
});
