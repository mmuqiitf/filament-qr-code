<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

/**
 * Linear symbologies the barcode generator can draw.
 *
 * Not to be confused with BarcodeFormat, which lists what the scanner reads.
 */
enum BarcodeType: string
{
    case Code128 = 'code128';
    case Ean13 = 'ean13';
    case Ean8 = 'ean8';
    case UpcA = 'upca';
    case Code39 = 'code39';
    case Itf14 = 'itf14';

    /** Human-readable name, e.g. "EAN-13". */
    public function getLabel(): string
    {
        return match ($this) {
            self::Code128 => 'Code 128',
            self::Ean13 => 'EAN-13',
            self::Ean8 => 'EAN-8',
            self::UpcA => 'UPC-A',
            self::Code39 => 'Code 39',
            self::Itf14 => 'ITF-14',
        };
    }

    /**
     * Digit count without and with the check digit, or null for
     * variable-length alphanumeric symbologies.
     *
     * @return array{int, int}|null
     */
    public function getDigitLengths(): ?array
    {
        return match ($this) {
            self::Ean13 => [12, 13],
            self::Ean8 => [7, 8],
            self::UpcA => [11, 12],
            self::Itf14 => [13, 14],
            default => null,
        };
    }

    /** Quiet zone in modules added on each side by default. ITF-14 draws its own. */
    public function getDefaultMargin(): int
    {
        return $this === self::Itf14 ? 0 : 10;
    }
}
