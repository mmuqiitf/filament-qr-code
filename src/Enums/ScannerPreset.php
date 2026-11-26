<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum ScannerPreset: string
{
    case Retail = 'retail';
    case Warehouse = 'warehouse';
    case Inventory = 'inventory';
    case Ticketing = 'ticketing';
    case Gs1 = 'gs1';

    public function getLabel(): string
    {
        return match ($this) {
            self::Retail => 'Retail',
            self::Warehouse => 'Warehouse',
            self::Inventory => 'Inventory',
            self::Ticketing => 'Ticketing',
            self::Gs1 => 'GS1',
        };
    }

    /**
     * @return list<BarcodeFormat>
     */
    public function formats(): array
    {
        return match ($this) {
            self::Retail => [
                BarcodeFormat::Ean13,
                BarcodeFormat::Ean8,
                BarcodeFormat::UpcA,
                BarcodeFormat::UpcE,
                BarcodeFormat::Code128,
            ],
            self::Warehouse => [
                BarcodeFormat::Code128,
                BarcodeFormat::Code39,
                BarcodeFormat::Itf,
                BarcodeFormat::QrCode,
                BarcodeFormat::DataMatrix,
            ],
            self::Inventory => [
                BarcodeFormat::Code128,
                BarcodeFormat::Ean13,
                BarcodeFormat::QrCode,
                BarcodeFormat::DataMatrix,
            ],
            self::Ticketing => [
                BarcodeFormat::QrCode,
                BarcodeFormat::Aztec,
            ],
            self::Gs1 => [
                BarcodeFormat::Code128,
                BarcodeFormat::DataMatrix,
                BarcodeFormat::QrCode,
            ],
        };
    }

    public function fps(): int
    {
        return match ($this) {
            self::Retail => 25,
            self::Warehouse => 20,
            self::Inventory => 20,
            self::Ticketing => 30,
            self::Gs1 => 20,
        };
    }

    /**
     * @return array{width: float, height: float}
     */
    public function scanRegion(): array
    {
        return match ($this) {
            self::Retail => ['width' => 0.85, 'height' => 0.35],
            self::Warehouse => ['width' => 0.80, 'height' => 0.55],
            self::Inventory => ['width' => 0.75, 'height' => 0.65],
            self::Ticketing => ['width' => 0.65, 'height' => 0.65],
            self::Gs1 => ['width' => 0.80, 'height' => 0.60],
        };
    }

    public function preferRearCamera(): bool
    {
        return true;
    }

    public function cameraControls(): bool
    {
        return true;
    }
}
