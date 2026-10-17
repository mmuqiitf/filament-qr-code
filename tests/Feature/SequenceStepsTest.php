<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

it("fields(['a','b']) yields the same payload as steps([ScanStep::make('a'), …])", function () {
    $fromFields = QrScanSequence::make(['a', 'b'])->getScanPayload();
    $fromSteps = QrScanSequence::make()->steps([ScanStep::make('a'), ScanStep::make('b')])->getScanPayload();

    expect($fromSteps)->toBe($fromFields)
        ->and($fromSteps['fields'])->toBe([
            ['key' => 'a', 'label' => 'A'],
            ['key' => 'b', 'label' => 'B'],
        ]);
});

it('adapts keyed labels and key/label arrays into steps', function () {
    $sequence = QrScanSequence::make([
        'purchase_order',
        'sku' => 'Product SKU',
        ['key' => 'lot', 'label' => 'Lot number'],
    ]);

    expect(array_map(fn (ScanStep $step): array => [$step->getKey(), $step->getLabel()], $sequence->getScanSteps()))->toBe([
        ['purchase_order', 'Purchase Order'],
        ['sku', 'Product SKU'],
        ['lot', 'Lot number'],
    ])
        ->and($sequence->getScanFields())->toBe(
            QrScanSequence::make()->steps([
                ScanStep::make('purchase_order'),
                ScanStep::make('sku')->label('Product SKU'),
                ScanStep::make('lot')->label('Lot number'),
            ])->getScanFields(),
        );
});

it('accepts a closure for steps', function () {
    $sequence = QrScanSequence::make()->steps(fn (): array => [ScanStep::make('a')]);

    expect($sequence->getScanFields())->toBe([['key' => 'a', 'label' => 'A']]);
});

it('describes a step', function () {
    $step = ScanStep::make('lot')->optional()->validateUsing(['min:3']);

    expect($step->isOptional())->toBeTrue()
        ->and($step->hasChecks())->toBeTrue()
        ->and(ScanStep::make('a')->hasChecks())->toBeFalse()
        ->and(ScanStep::make('a')->isOptional())->toBeFalse()
        ->and($step->toField())->toBe(['key' => 'lot', 'label' => 'Lot']);
});
