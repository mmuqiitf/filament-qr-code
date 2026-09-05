<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

it('can instantiate QrCodeService via make', function () {
    $service = QrCodeService::make();
    expect($service)->toBeInstanceOf(QrCodeService::class);
});

it('can generate an SVG QR code', function () {
    $service = QrCodeService::make()
        ->format(QrFormat::Svg)
        ->size(200)
        ->margin(2)
        ->color('#000000')
        ->backgroundColor('#ffffff')
        ->generate('https://filamentphp.com');

    $raw = $service->getRaw();
    expect($raw)
        ->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');

    $dataUri = $service->toDataUri();
    expect($dataUri)
        ->toStartWith('data:image/svg+xml;base64,');
});

it('can generate a PNG QR code', function () {
    $service = QrCodeService::make()
        ->format(QrFormat::Png)
        ->size(150)
        ->margin(1)
        ->generate('TEST-PAYLOAD-12345');

    $raw = $service->getRaw();
    expect($raw)->toBeString();

    // Check PNG signature header (\x89PNG)
    expect(str_starts_with($raw, "\x89PNG\r\n\x1a\n"))->toBeTrue();

    $dataUri = $service->toDataUri();
    expect($dataUri)->toStartWith('data:image/png;base64,');
});

it('can generate a PNG with text overlay', function () {
    $service = QrCodeService::make()
        ->size(200)
        ->withText('BATCH-9999', 14, '#111827')
        ->generate('BATCH-9999');

    $raw = $service->getRaw();
    expect(str_starts_with($raw, "\x89PNG\r\n\x1a\n"))->toBeTrue();
});

it('can create a streamed download response', function () {
    $response = QrCodeService::make()
        ->format(QrFormat::Svg)
        ->fileName('custom-qr')
        ->generate('DOWNLOAD_TEST')
        ->download();

    expect($response->headers->get('content-type'))->toBe('image/svg+xml')
        ->and($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->headers->get('content-disposition'))->toContain('custom-qr.svg');
});

it('supports custom font path and graceful GD fallback for text overlay', function () {
    $service = QrCodeService::make()
        ->size(180)
        ->font('non-existent-font.ttf')
        ->withText('FALLBACK-TEST', 12)
        ->generate('FALLBACK-TEST');

    $raw = $service->getRaw();
    expect(str_starts_with($raw, "\x89PNG\r\n\x1a\n"))->toBeTrue();
});
