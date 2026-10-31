<?php

declare(strict_types=1);

const PRODUCT_NAME = 'Filament QR & Barcode';

function brandingRoot(string $path): string
{
    return (string) file_get_contents(__DIR__.'/../../'.$path);
}

it('titles the user-facing docs with the product name', function (string $path, string $heading) {
    expect(brandingRoot($path))->toStartWith($heading.' '.PRODUCT_NAME);
})->with([
    ['README.md', '#'],
    ['llms-full.txt', '#'],
    ['resources/boost/guidelines/core.blade.php', '##'],
]);

it('no longer calls the package "Filament QR Code"', function (string $path) {
    expect(brandingRoot($path))->not->toContain('Filament QR Code');
})->with(['README.md', 'SECURITY.md', 'llms-full.txt', 'resources/boost/guidelines/core.blade.php', 'resources/boost/skills/filament-qr-code/SKILL.md']);

it('describes the package as QR and barcode in composer.json, keeping the package name', function () {
    $composer = json_decode(brandingRoot('composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['name'])->toBe('mmuqiitf/filament-qr-code')
        ->and($composer['description'])->toStartWith(PRODUCT_NAME.':')
        ->and($composer['keywords'] ?? [])->toContain('barcode');
});

it('supports 1.x only in the security policy', function () {
    $policy = brandingRoot('SECURITY.md');

    expect($policy)->toContain(PRODUCT_NAME)
        ->toMatch('/\|\s*1\.x\s*\|\s*✅/')
        ->toMatch('/\|\s*0\.x\s*\|\s*❌/')
        ->toContain('within 7 days')
        ->toContain('## Scope')
        ->toContain('generator.logo_base_paths')
        ->not->toContain('as soon as possible');
});
