<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum BarcodeFormat: string
{
    case QrCode = 'QR_CODE';
    case Aztec = 'AZTEC';
    case Codabar = 'CODABAR';
    case Code39 = 'CODE_39';
    case Code93 = 'CODE_93';
    case Code128 = 'CODE_128';
    case DataMatrix = 'DATA_MATRIX';
    case Maxicode = 'MAXICODE';
    case Itf = 'ITF';
    case Ean13 = 'EAN_13';
    case Ean8 = 'EAN_8';
    case UpcA = 'UPC_A';
    case UpcE = 'UPC_E';

    public function getLabel(): string
    {
        return match ($this) {
            self::QrCode => 'QR Code',
            self::Aztec => 'Aztec Code',
            self::Codabar => 'Codabar',
            self::Code39 => 'Code 39',
            self::Code93 => 'Code 93',
            self::Code128 => 'Code 128',
            self::DataMatrix => 'Data Matrix',
            self::Maxicode => 'MaxiCode',
            self::Itf => 'ITF (Interleaved 2 of 5)',
            self::Ean13 => 'EAN-13',
            self::Ean8 => 'EAN-8',
            self::UpcA => 'UPC-A',
            self::UpcE => 'UPC-E',
        };
    }
}
