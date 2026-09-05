<?php

declare(strict_types=1);

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
