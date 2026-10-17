<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum ScanInput: string
{
    case Camera = 'camera';
    case Hardware = 'hardware';
    case Upload = 'upload';
    case Manual = 'manual';

    public static function fromClient(string $value): self
    {
        return self::tryFrom($value) ?? self::Camera;
    }
}
