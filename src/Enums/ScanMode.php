<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum ScanMode: string
{
    case Single = 'single';        // Scan once and close
    case Continuous = 'continuous'; // Keep scanning
    case Sequence = 'sequence';     // Scan multiple fields in order

    public function label(): string
    {
        return match ($this) {
            self::Single => __('filament-qr-code::messages.mode.single'),
            self::Continuous => __('filament-qr-code::messages.mode.continuous'),
            self::Sequence => __('filament-qr-code::messages.mode.sequence'),
        };
    }
}
