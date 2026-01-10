<?php

use Mmuqiitf\FilamentQrCode\QrCodePlugin;

it('can be instantiated with make', function () {
    $plugin = QrCodePlugin::make();

    expect($plugin)->toBeInstanceOf(QrCodePlugin::class);
});

it('has correct id', function () {
    $plugin = QrCodePlugin::make();

    expect($plugin->getId())->toBe('filament-qr-code');
});

it('can enable qr code scanner page', function () {
    $plugin = QrCodePlugin::make()
        ->qrCodeScannerPage();

    expect($plugin->hasQrCodeScannerPage())->toBeTrue();
});

it('can disable qr code scanner page', function () {
    $plugin = QrCodePlugin::make()
        ->qrCodeScannerPage(false);

    expect($plugin->hasQrCodeScannerPage())->toBeFalse();
});

it('can register additional pages', function () {
    $plugin = QrCodePlugin::make()
        ->pages(['App\\Filament\\Pages\\CustomPage']);

    expect($plugin->getPages())->toBe(['App\\Filament\\Pages\\CustomPage']);
});
