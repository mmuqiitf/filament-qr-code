<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum CameraFacing: string
{
    case Front = 'user';
    case Back = 'environment';
    case Auto = 'auto';

    public function label(): string
    {
        return match ($this) {
            self::Front => __('filament-qr-code::messages.camera.front'),
            self::Back => __('filament-qr-code::messages.camera.back'),
            self::Auto => __('filament-qr-code::messages.camera.auto'),
        };
    }
}
