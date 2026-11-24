<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

/**
 * Symbologies the barcode generator can draw, including 2D Data Matrix
 * and PDF417. Not to be confused with BarcodeFormat, which lists what
 * the scanner reads.
 */
enum BarcodeType: string
{
    case Code128 = 'code128';
    case Ean13 = 'ean13';
    case Ean8 = 'ean8';
    case UpcA = 'upca';
    case Code39 = 'code39';
    case Itf14 = 'itf14';
    case Gs1128 = 'gs1-128';
    case DataMatrix = 'datamatrix';
    case Gs1DataMatrix = 'gs1-datamatrix';
    case Pdf417 = 'pdf417';
    case Pdf417Compact = 'pdf417-compact';

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
            self::Gs1128 => 'GS1-128',
            self::DataMatrix => 'Data Matrix',
            self::Gs1DataMatrix => 'GS1 DataMatrix',
            self::Pdf417 => 'PDF417',
            self::Pdf417Compact => 'Compact PDF417',
        };
    }

    /** Data Matrix and PDF417 (and their variants). */
    public function isTwoDimensional(): bool
    {
        return match ($this) {
            self::DataMatrix, self::Gs1DataMatrix, self::Pdf417, self::Pdf417Compact => true,
            default => false,
        };
    }

    /**
     * Vertical size of one matrix row, in modules. Data Matrix modules are
     * square; PDF417 rows are 3X tall (ISO 15438 recommended minimum).
     */
    public function rowAspect(): int
    {
        return match ($this) {
            self::Pdf417, self::Pdf417Compact => 3,
            default => 1,
        };
    }

    /** Human-readable text is on by default for 1D types and off for 2D. */
    public function showsTextByDefault(): bool
    {
        return ! $this->isTwoDimensional();
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

    /**
     * Quiet zone in modules. ITF-14 draws its own. Data Matrix uses the
     * ISO 1-module zone; PDF417 uses 2 modules; 1D types use 10.
     */
    public function getDefaultMargin(): int
    {
        return match ($this) {
            self::Itf14 => 0,
            self::DataMatrix, self::Gs1DataMatrix => 1,
            self::Pdf417, self::Pdf417Compact => 2,
            default => 10,
        };
    }
}
