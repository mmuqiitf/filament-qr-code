<?php

declare(strict_types=1);

it('loads namespaced package translations', function () {
    expect(__('filament-qr-code::ui.scan'))->toBe('Scan')
        ->and(__('filament-qr-code::ui.batch_qr_collector'))->toBe('Batch QR Collector')
        ->and(__('filament-qr-code::ui.camera'))->toBe('Camera:');
});
