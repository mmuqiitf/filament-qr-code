<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Mmuqiitf\FilamentQrCode\Http\Middleware\ThrottleQrImage;

it('registers the camelCase image route names', function () {
    expect(Route::has('filamentQrCode.image'))->toBeTrue()
        ->and(route('filamentQrCode.image', ['data' => 'x']))->toContain('filament-qr-code/qr-image')
        ->and(Route::has('filamentQrCode.barcode'))->toBeTrue()
        ->and(route('filamentQrCode.barcode', ['data' => 'x']))->toContain('filament-qr-code/barcode-image')
        ->and(Route::has('filamentQrCode.labels'))->toBeTrue()
        ->and(route('filamentQrCode.labels', ['token' => 'abc']))->toContain('filament-qr-code/labels/abc');
});

it('signs and throttles both image routes and adds no extra middleware by default', function () {
    $expected = ['signed', ThrottleQrImage::class];

    expect(Route::getRoutes()->getByName('filamentQrCode.image')->gatherMiddleware())->toBe($expected)
        ->and(Route::getRoutes()->getByName('filamentQrCode.barcode')->gatherMiddleware())->toBe($expected);
});

it('serves signed urls generated from the route name', function () {
    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'x']))->assertOk();
    $this->get(URL::signedRoute('filamentQrCode.barcode', ['data' => 'x']))->assertOk();
});
