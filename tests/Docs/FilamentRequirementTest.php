<?php

declare(strict_types=1);

// Filament releases before 5.8.3 carry security advisories, so Composer will
// not install them and prefer-lowest CI could never test them. Keep every
// filament/* constraint and the documented requirement on the same minimum.
const FILAMENT_MINIMUM = '5.8.3';

it('requires the same tested Filament minimum for every filament package', function () {
    $composer = json_decode((string) file_get_contents(__DIR__.'/../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    $filament = array_filter(
        $composer['require'],
        fn (string $package): bool => str_starts_with($package, 'filament/'),
        ARRAY_FILTER_USE_KEY,
    );

    expect($filament)->not->toBeEmpty()
        ->and(array_unique(array_values($filament)))->toBe(['^'.FILAMENT_MINIMUM]);
});

it('documents the Filament minimum in the README and getting started guide', function (string $path) {
    expect((string) file_get_contents(__DIR__.'/../../'.$path))->toContain('Filament '.FILAMENT_MINIMUM);
})->with(['README.md', 'docs/getting-started.md']);
