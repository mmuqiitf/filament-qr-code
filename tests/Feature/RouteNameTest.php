<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

it('registers the camelCase image route names', function () {
    expect(Route::has('filamentQrCode.image'))->toBeTrue()
        ->and(route('filamentQrCode.image', ['data' => 'x']))->toContain('filament-qr-code/qr-image')
        ->and(Route::has('filamentQrCode.barcode'))->toBeTrue()
        ->and(route('filamentQrCode.barcode', ['data' => 'x']))->toContain('filament-qr-code/barcode-image');
});

it('serves signed urls generated from the route name', function () {
    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'x']))->assertOk();
    $this->get(URL::signedRoute('filamentQrCode.barcode', ['data' => 'x']))->assertOk();
});
