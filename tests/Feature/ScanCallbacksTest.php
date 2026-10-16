<?php

declare(strict_types=1);

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Event;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);

it('accepted scan dispatches ScanAccepted and QrCodeScanned with source=hardware', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);
    $productId = ScanCheckProduct::query()->where('sku', 'SKU-1')->value('id');

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-1', 'hardware');

    Event::assertDispatched(ScanAccepted::class, fn (ScanAccepted $event): bool => $event->field === 'sku'
        && $event->result->code === 'SKU-1'
        && $event->result->record?->getKey() === $productId);
    Event::assertDispatched(QrCodeScanned::class, fn (QrCodeScanned $event): bool => $event->code === 'SKU-1'
        && $event->source === 'hardware'
        && $event->field === 'sku'
        && $event->context === ['record_key' => $productId]);
    Event::assertNotDispatched(ScanRejected::class);
});

it('rejected scan dispatches ScanRejected only', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-404', 'camera');

    Event::assertDispatched(ScanRejected::class, fn (ScanRejected $event): bool => $event->field === 'sku'
        && $event->result->message === 'No record found for "SKU-404".'
        && $event->result->source?->value === 'camera');
    Event::assertNotDispatched(ScanAccepted::class);
    Event::assertNotDispatched(QrCodeScanned::class);
});

it('does not dispatch the qr-scan-rejected browser event from the server', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-404')->assertNotDispatched('qr-scan-rejected');
});
