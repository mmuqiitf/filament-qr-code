<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Rendering;

use InvalidArgumentException;

final class HexColor
{
    /**
     * @return array{r: int<0, 255>, g: int<0, 255>, b: int<0, 255>}
     */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            throw new InvalidArgumentException("Invalid hex color [{$hex}]. Expected 3 or 6 hex digits, e.g. '#0f172a'.");
        }

        return [
            'r' => min(255, max(0, (int) hexdec(substr($hex, 0, 2)))),
            'g' => min(255, max(0, (int) hexdec(substr($hex, 2, 2)))),
            'b' => min(255, max(0, (int) hexdec(substr($hex, 4, 2)))),
        ];
    }
}
