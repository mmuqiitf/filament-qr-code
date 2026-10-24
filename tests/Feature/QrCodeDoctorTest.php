<?php

declare(strict_types=1);

it('reports install health through the doctor command', function () {
    // git checkout writes resources/dist before resources/js, so on CI the
    // sources can land a second later than the bundle. Pin a built-after mtime
    // for this check, then restore it.
    $distJs = __DIR__.'/../../resources/dist/filament-qr-code.js';
    $originalMtime = (int) filemtime($distJs);
    touch($distJs, time() + 60);

    try {
        $this->artisan('qr-code:doctor')
            ->assertSuccessful()
            ->expectsOutputToContain('PNG backend available')
            ->expectsOutputToContain('Compiled assets are newer than sources')
            ->expectsOutputToContain('Signed preview route registered');
    } finally {
        touch($distJs, $originalMtime);
    }
});

it('translates UI strings to Indonesian', function () {
    expect(trans('filament-qr-code::ui.scan', [], 'id'))->toBe('Pindai')
        ->and(trans('filament-qr-code::ui.done', [], 'id'))->toBe('Selesai')
        ->and(trans('filament-qr-code::ui.scan', [], 'en'))->toBe('Scan');
});
