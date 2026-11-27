<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Unit;

use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;

class DummyComponentWithScanner
{
    use HasHardwareScanner;

    public function evaluate(mixed $value): mixed
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}

it('evaluates hardware scanner configuration correctly', function (): void {
    $component = new DummyComponentWithScanner;

    expect($component->isHardwareScannerEnabled())->toBeTrue()
        ->and($component->getBurstThresholdMs())->toBe(50)
        ->and($component->shouldPreventFormSubmit())->toBeTrue();

    $component->hardwareScanner(
        enabled: fn (): bool => false,
        burstThresholdMs: fn (): int => 25,
        preventFormSubmit: fn (): bool => false,
    );

    expect($component->isHardwareScannerEnabled())->toBeFalse()
        ->and($component->getBurstThresholdMs())->toBe(25)
        ->and($component->shouldPreventFormSubmit())->toBeFalse();
});

it('exposes hardware scanner terminator and minimum-length configuration', function (): void {
    $component = new DummyComponentWithScanner;

    expect($component->getTerminators())->toBe(['Enter', 'Tab'])
        ->and($component->getMinBarcodeLength())->toBe(2);

    $component->hardwareScanner(
        enabled: true,
        terminators: ['Enter'],
        minBarcodeLength: 5,
    );

    expect($component->getTerminators())->toBe(['Enter'])
        ->and($component->getMinBarcodeLength())->toBe(5);
});
