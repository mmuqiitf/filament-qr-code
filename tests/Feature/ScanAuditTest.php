<?php

declare(strict_types=1);

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Listeners\LogQrCodeScan;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

it('dispatches an audit event for server-observed scans', function () {
    Event::fake([QrCodeScanned::class]);

    QrScanner::make('sku')->triggerOnScan('SKU-1');
    QrCollectAction::make()->handleScan('SKU-2');

    Event::assertDispatched(QrCodeScanned::class, fn (QrCodeScanned $event) => $event->code === 'SKU-1'
        && $event->source === 'scanner-field'
        && $event->field === 'sku');
    Event::assertDispatched(QrCodeScanned::class, fn (QrCodeScanned $event) => $event->code === 'SKU-2'
        && $event->source === 'collect-action');
});

it('logs scans only when auditing is enabled', function () {
    $logged = [];

    Log::listen(function (MessageLogged $message) use (&$logged): void {
        $logged[] = [$message->level, $message->message, $message->context];
    });

    config()->set('qr-code.audit.enabled', false);

    (new LogQrCodeScan)->handle(new QrCodeScanned('SKU-1', 'scanner-field', 'sku'));

    expect($logged)->toBe([]);

    config()->set('qr-code.audit.enabled', true);

    (new LogQrCodeScan)->handle(new QrCodeScanned('SKU-1', 'scanner-field', 'sku'));

    expect($logged)->toHaveCount(1)
        ->and($logged[0][1])->toBe('filament-qr-code.scan')
        ->and($logged[0][2]['code'])->toBe('SKU-1')
        ->and($logged[0][2]['source'])->toBe('scanner-field');

    config()->set('qr-code.audit.enabled', false);
});
