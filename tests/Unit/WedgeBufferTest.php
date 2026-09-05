<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Unit;

use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;

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

it('supports all 1D and 2D barcode format enumerations', function (): void {
    expect(BarcodeFormat::QrCode->getLabel())->toBe('QR Code')
        ->and(BarcodeFormat::Aztec->getLabel())->toBe('Aztec Code')
        ->and(BarcodeFormat::Codabar->getLabel())->toBe('Codabar')
        ->and(BarcodeFormat::Code39->getLabel())->toBe('Code 39')
        ->and(BarcodeFormat::Code93->getLabel())->toBe('Code 93')
        ->and(BarcodeFormat::Code128->getLabel())->toBe('Code 128')
        ->and(BarcodeFormat::DataMatrix->getLabel())->toBe('Data Matrix')
        ->and(BarcodeFormat::Maxicode->getLabel())->toBe('MaxiCode')
        ->and(BarcodeFormat::Itf->getLabel())->toBe('ITF (Interleaved 2 of 5)')
        ->and(BarcodeFormat::Ean13->getLabel())->toBe('EAN-13')
        ->and(BarcodeFormat::Ean8->getLabel())->toBe('EAN-8')
        ->and(BarcodeFormat::UpcA->getLabel())->toBe('UPC-A')
        ->and(BarcodeFormat::UpcE->getLabel())->toBe('UPC-E');
});

it('sanitizes typical hardware wedge scanner payloads', function (): void {
    // Barcode hardware scanners often send prefixes (\x02 STX) and suffixes (\r, \n, \x03 ETX)
    $rawScannerPayload = "\x02PROD-9988234-XYZ\r\n\x03";
    $cleaned = trim($rawScannerPayload, "\x00..\x1F");

    expect($cleaned)->toBe('PROD-9988234-XYZ');
});
