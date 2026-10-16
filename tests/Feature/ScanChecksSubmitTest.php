<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);

it('submit with an unknown SKU fails with record_not_found', function (): void {
    ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'))
        ->set('data.sku', 'SKU-404')
        ->call('submit')
        ->assertHasErrors(['data.sku' => ['No record found for "SKU-404".']])
        ->assertSet('submitted', false);
});

it('submit re-runs rules and closures with the same messages', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->validateUsing(['max:10'])
        ->lookup(ScanCheckProduct::class, 'sku')
        ->validateUsing(fn (?ScanCheckProduct $record): ?string => $record?->stock > 0 ? null : 'Out of stock'));

    $test->set('data.sku', 'SKU-1234567890')->call('submit')->assertHasErrors(['data.sku']);
    expect($test->errors()->get('data.sku'))->toBe(['The Sku field must not be greater than 10 characters.']);

    $test->set('data.sku', 'SKU-EMPTY')->call('submit');
    expect($test->errors()->get('data.sku'))->toBe(['Out of stock']);

    $test->set('data.sku', 'SKU-1')->call('submit')->assertHasNoErrors()->assertSet('submitted', true);
});

it('lets a blank value through when the field is not required', function (): void {
    ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);
});

it('adds no submit rule when no checks are configured', function (): void {
    ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner)
        ->set('data.sku', 'anything')
        ->call('submit')
        ->assertHasNoErrors();
});
