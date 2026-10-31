<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

it('gives the facade an API summary', function () {
    $api = (string) file_get_contents(__DIR__.'/../../docs/api.md');

    expect($api)->toContain('`Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode` — ');
});

it('declares every public QrCodeService method on the facade', function () {
    $docBlock = (string) (new ReflectionClass(FilamentQrCode::class))->getDocComment();

    $methods = collect((new ReflectionClass(QrCodeService::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->reject(fn (ReflectionMethod $method): bool => str_starts_with($method->getName(), '__'))
        ->map(fn (ReflectionMethod $method): string => $method->getName());

    foreach ($methods as $method) {
        expect($docBlock)->toMatch('/@method static \S+ '.$method.'\(/');
    }
});

it('documents the facade in the generating guide', function () {
    $guide = (string) file_get_contents(__DIR__.'/../../docs/generating.md');

    expect($guide)->toContain('### The `FilamentQrCode` facade')
        ->toContain('FilamentQrCode::cacheTtl(');
});

it('starts a fresh builder from make() even though the facade root is cached', function () {
    $first = FilamentQrCode::make()->size(120);

    expect(FilamentQrCode::getFacadeRoot())->toBe(FilamentQrCode::getFacadeRoot())
        ->and(FilamentQrCode::make())->not->toBe($first)
        ->and(FilamentQrCode::make())->not->toBe(FilamentQrCode::getFacadeRoot());
});
