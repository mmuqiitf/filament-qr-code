<?php

declare(strict_types=1);

it('reports install health through the doctor command', function () {
    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain('PNG backend available')
        ->expectsOutputToContain('Compiled assets are newer than sources')
        ->expectsOutputToContain('Signed preview route registered');
});

it('translates UI strings to Indonesian', function () {
    expect(trans('filament-qr-code::ui.scan', [], 'id'))->toBe('Pindai')
        ->and(trans('filament-qr-code::ui.done', [], 'id'))->toBe('Selesai')
        ->and(trans('filament-qr-code::ui.scan', [], 'en'))->toBe('Scan');
});
