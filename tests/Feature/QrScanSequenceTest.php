<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

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
