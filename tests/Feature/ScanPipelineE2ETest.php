<?php

declare(strict_types=1);

use Filament\Schemas\Components\Utilities\Set;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);

it('a rendered lookup scanner exposes the browser contract and handles the browser\'s handleScan call', function (): void {
    $test = ScanChecksTestComponent::test(function (QrScanner $scanner): QrScanner {
        return $scanner
            ->lookup(ScanCheckProduct::class, 'sku')
            ->onScanned(function (ScanResult $result, Set $set): void {
                $set('note', "stock {$result->record->stock}");
            });
    });

    $scanner = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);

    expect($scanner->getScanPayload())->toMatchArray([
        'serverCheck' => true,
        'componentKey' => 'form.sku',
        'cooldownMs' => 1500,
        'clearOnReject' => true,
        'scanFailedMessage' => 'The scan could not be checked. Please try again.',
    ]);

    $test->assertSeeHtml('qrScanner(')
        ->assertSeeHtml('data-field-name="sku"');

    $test->call('callSchemaComponentMethod', 'form.sku', 'handleScan', ['code' => 'SKU-1', 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === true)
        ->assertSet('data.sku', 'SKU-1')
        ->assertSet('data.note', 'stock 5');

    $test->call('callSchemaComponentMethod', 'form.sku', 'handleScan', ['code' => 'SKU-404', 'source' => 'hardware'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'No record found for "SKU-404".')
        ->assertSet('data.sku', null)
        ->assertNotDispatched('qr-scan-rejected');
});

it('renders an unconfigured scanner without server checks or cooldown', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner): QrScanner => $scanner);

    $scanner = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);

    expect($scanner->getScanPayload())->toMatchArray(['serverCheck' => false, 'cooldownMs' => 0]);
});
