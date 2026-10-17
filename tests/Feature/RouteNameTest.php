<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

it('registers the camelCase qr image route name', function () {
    expect(Route::has('filamentQrCode.image'))->toBeTrue()
        ->and(route('filamentQrCode.image', ['data' => 'x']))->toContain('filament-qr-code/qr-image');
});

it('keeps the deprecated kebab-case route name resolving to the same url', function () {
    expect(route('filament-qr-code.image', ['data' => 'x']))
        ->toBe(route('filamentQrCode.image', ['data' => 'x']));
});

it('serves signed urls generated from both route names', function (string $name) {
    $this->get(URL::signedRoute($name, ['data' => 'x']))->assertOk();
})->with(['filamentQrCode.image', 'filament-qr-code.image']);
