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
it('onScanned receives ScanResult, Set and Get and can fill a sibling field', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->onScanned(function (ScanResult $result, Set $set, Get $get): void {
            $set('note', "{$get('sku')} x {$result->record->stock}");

        }));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertSet('data.sku', 'SKU-1')
        ->assertSet('data.note', 'SKU-1 x 5');
});

it('runs onScanned after state is set and before the events', function (): void {
    $order = [];
    Event::listen(ScanAccepted::class, function () use (&$order): void {
        $order[] = 'ScanAccepted';
    });
    Event::listen(QrCodeScanned::class, function () use (&$order): void {
        $order[] = 'QrCodeScanned';
    });

    $test = ScanChecksTestComponent::test(function (QrScanner $scanner) use (&$order): QrScanner {
        return $scanner
            ->onScanned(function (Get $get) use (&$order): void {
                $order[] = "onScanned:{$get('sku')}";
            });
    });

    ScanChecksTestComponent::scan($test, 'ABC');

    expect($order)->toBe(['onScanned:ABC', 'ScanAccepted', 'QrCodeScanned']);
});

it('does not run onScanned for a rejected scan', function (): void {
    $calls = 0;

    $test = ScanChecksTestComponent::test(function (QrScanner $scanner) use (&$calls): QrScanner {
        return $scanner
            ->lookup(ScanCheckProduct::class, 'sku')
            ->onScanned(function () use (&$calls): void {
                $calls++;
            });
    });

    ScanChecksTestComponent::scan($test, 'SKU-404');

    expect($calls)->toBe(0);
});

it('counts an onScanned callback as a server check', function (): void {
    expect(QrScanner::make('sku')->onScanned(fn () => null)->hasServerScanChecks())->toBeTrue();
});
it('rejected scan runs onRejected and clears state when clearOnReject', function (): void {
    $messages = [];

    $test = ScanChecksTestComponent::test(function (QrScanner $scanner) use (&$messages): QrScanner {
        return $scanner
            ->lookup(ScanCheckProduct::class, 'sku')
            ->onRejected(function (ScanResult $result, Set $set) use (&$messages): void {
                $messages[] = $result->message;
                $set('note', 'rejected');
            });
    });

    $test->set('data.sku', 'SKU-1');

    ScanChecksTestComponent::scan($test, 'SKU-404')
        ->assertSet('data.sku', null)
        ->assertSet('data.note', 'rejected');

    expect($messages)->toBe(['No record found for "SKU-404".']);
});

it('keeps the previous state when clearOnReject is off', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->clearOnReject(false));

    $test->set('data.sku', 'SKU-1');

    ScanChecksTestComponent::scan($test, 'SKU-404')->assertSet('data.sku', 'SKU-1');
});

it('reads the clearOnReject default from config', function (): void {
    expect(QrScanner::make('sku')->shouldClearOnReject())->toBeTrue();

    config()->set('qr-code.scan.clear_on_reject', false);

    expect(QrScanner::make('sku')->shouldClearOnReject())->toBeFalse()
        ->and(QrScanner::make('sku')->clearOnReject()->shouldClearOnReject())->toBeTrue();
});

it('runs onRejected before ScanRejected', function (): void {
    $order = [];
    Event::listen(ScanRejected::class, function () use (&$order): void {
        $order[] = 'ScanRejected';
    });

    $test = ScanChecksTestComponent::test(function (QrScanner $scanner) use (&$order): QrScanner {
        return $scanner
            ->validateUsing(['max:2'])
            ->onRejected(function () use (&$order): void {
                $order[] = 'onRejected';
            });
    });

    ScanChecksTestComponent::scan($test, 'TOO-LONG');

    expect($order)->toBe(['onRejected', 'ScanRejected']);
});

it('counts an onRejected callback as a server check', function (): void {
    expect(QrScanner::make('sku')->onRejected(fn () => null)->hasServerScanChecks())->toBeTrue();
});
