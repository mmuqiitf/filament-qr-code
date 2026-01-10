<?php

use Mmuqiitf\FilamentQrCode\Enums\CameraFacing;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeScannerInput;

it('can be instantiated', function () {
    $input = QrCodeScannerInput::make('barcode');

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure camera facing', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->cameraFacing(CameraFacing::Back);

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('has useBackCamera helper', function () {
    $input = QrCodeScannerInput::make('barcode')->useBackCamera();

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('has useFrontCamera helper', function () {
    $input = QrCodeScannerInput::make('barcode')->useFrontCamera();

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can set scan mode', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->scanMode(ScanMode::Continuous);

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('has continuous helper', function () {
    $input = QrCodeScannerInput::make('barcode')->continuous();

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('has sequence helper', function () {
    $input = QrCodeScannerInput::make('barcode')->sequence();

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure scan delay', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->scanDelay(2000);

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure fps', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->fps(15);

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure qrbox size', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->qrboxSize(300);

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can show or hide preview', function () {
    $inputWithPreview = QrCodeScannerInput::make('barcode')
        ->showPreview(true);

    $inputWithoutPreview = QrCodeScannerInput::make('barcode')
        ->hidePreview();

    expect($inputWithPreview)->toBeInstanceOf(QrCodeScannerInput::class);
    expect($inputWithoutPreview)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure beep on scan', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->beepOnScan(false);

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure vibrate on scan', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->vibrateOnScan(false);

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure modal width', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->modalWidth('4xl');

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});

it('can configure slide over', function () {
    $input = QrCodeScannerInput::make('barcode')
        ->slideOver();

    expect($input)->toBeInstanceOf(QrCodeScannerInput::class);
});
