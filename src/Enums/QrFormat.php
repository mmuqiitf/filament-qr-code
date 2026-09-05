<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

enum QrFormat: string
{
    case Svg = 'svg';
    case Png = 'png';

    public function getMimeType(): string
    {
        return match ($this) {
            self::Svg => 'image/svg+xml',
            self::Png => 'image/png',
        };
    }

    public function getExtension(): string
    {
        return $this->value;
    }
}
