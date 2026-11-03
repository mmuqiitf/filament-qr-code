<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;

it('starts a barcode from the facade', function () {
    expect(FilamentQrCode::barcode())->toBeInstanceOf(BarcodeService::class);
});

it('gives every facade barcode its own builder', function () {
    $first = FilamentQrCode::barcode()->type(BarcodeType::Ean8);

    expect(FilamentQrCode::barcode())->not->toBe($first)
        ->and(FilamentQrCode::barcode()->getType())->toBe(BarcodeType::Code128);
});

it('renders the same bytes as BarcodeService', function () {
    $facade = FilamentQrCode::barcode()->type('ean13')->generate('5901234123457')->getRaw();
    $direct = BarcodeService::make()->type('ean13')->generate('5901234123457')->getRaw();

    expect($facade)->toBe($direct)->toStartWith('<?xml');
});
