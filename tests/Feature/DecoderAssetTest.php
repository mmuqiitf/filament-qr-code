<?php

declare(strict_types=1);

use Filament\Support\Assets\Js;
use Mmuqiitf\FilamentQrCode\FilamentQrCodeServiceProvider;

$dist = dirname(__DIR__, 2).'/resources/dist';

it('registers the camera decoder so filament:assets publishes it next to the scripts', function () use ($dist) {
    $decoder = collect(FilamentQrCodeServiceProvider::assets())
        ->first(fn ($asset): bool => $asset instanceof Js && $asset->getId() === 'filament-qr-code-decoder');

    expect($decoder)->not->toBeNull()
        ->and($decoder->isLoadedOnRequest())->toBeTrue()
        ->and($decoder->isModule())->toBeTrue()
        ->and(realpath((string) $decoder->getPath()))->toBe(realpath($dist.'/filament-qr-code-decoder.js'));
});

it('loads the decoder from the published sibling file', function () use ($dist) {
    $entry = file_get_contents($dist.'/filament-qr-code.js');

    expect($entry)->toMatch('/import\(\s*[`"\']\.\/filament-qr-code-decoder\.js[`"\']\s*\)/')
        ->and($entry)->not->toContain('./chunks/');
});

it('ships a self-contained decoder that imports nothing from the entry', function () use ($dist) {
    $decoder = file_get_contents($dist.'/filament-qr-code-decoder.js');

    expect($decoder)->not->toMatch('/\bfrom\s*["\'`]/')
        ->and($decoder)->not->toMatch('/(^|[;}])\s*import\s*[{*\w]/')
        ->and($decoder)->toContain('Html5Qrcode')
        ->and(is_dir($dist.'/chunks'))->toBeFalse();
});
