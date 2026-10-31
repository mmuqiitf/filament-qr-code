<?php

declare(strict_types=1);

it('translates UI strings to Indonesian', function () {
    expect(trans('filament-qr-code::ui.scan', [], 'id'))->toBe('Pindai')
        ->and(trans('filament-qr-code::ui.done', [], 'id'))->toBe('Selesai')
        ->and(trans('filament-qr-code::ui.scan', [], 'en'))->toBe('Scan');
});
