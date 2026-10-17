<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\ScanInput;

it('maps client source strings to scan inputs', function (string $value, ScanInput $expected): void {
    expect(ScanInput::fromClient($value))->toBe($expected);
})->with([
    'camera' => ['camera', ScanInput::Camera],
    'hardware' => ['hardware', ScanInput::Hardware],
    'upload' => ['upload', ScanInput::Upload],
    'unknown falls back to camera' => ['keyboard', ScanInput::Camera],
    'empty falls back to camera' => ['', ScanInput::Camera],
]);

it('exposes the wire values used by the browser', function (): void {
    expect(array_map(fn (ScanInput $input): string => $input->value, ScanInput::cases()))
        ->toBe(['camera', 'hardware', 'upload', 'manual']);
});
