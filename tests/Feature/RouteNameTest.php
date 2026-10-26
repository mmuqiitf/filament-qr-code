<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

it('registers the camelCase qr image route name', function () {
    expect(Route::has('filamentQrCode.image'))->toBeTrue()
        ->and(route('filamentQrCode.image', ['data' => 'x']))->toContain('filament-qr-code/qr-image');
});

it('serves signed urls generated from the route name', function () {
    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'x']))->assertOk();
});
