<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use InvalidArgumentException;

/**
 * Decode-box dimensions shared by camera components and qr-code:doctor.
 *
 * Values at or below 1.0 are viewfinder ratios. Integers greater than 1 are pixel maxima.
 *
 * @internal Not covered by semver; may change in any release.
 */
class ScanRegion
{
    /**
     * @return array{width: int|float, height: int|float}|null
     */
    public static function fromConfig(mixed $configured): ?array
    {
        if ($configured === null) {
            return null;
        }

        if (! is_array($configured)) {
            throw new InvalidArgumentException('Scan region must be an array of width and height.');
        }

        $width = $configured['width'] ?? $configured[0] ?? null;
        $height = $configured['height'] ?? $configured[1] ?? null;

        return self::pair($width, $height);
    }

    /**
     * @return array{width: int|float, height: int|float}|null
     */
    public static function tryFromConfig(mixed $configured): ?array
    {
        try {
            return self::fromConfig($configured);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return array{width: int|float, height: int|float}|null
     */
    public static function pair(mixed $width, mixed $height): ?array
    {
        if ($width === null && $height === null) {
            return null;
        }

        if ($width === null || $height === null) {
            throw new InvalidArgumentException('Both width and height must be provided when configuring scanRegion().');
        }

        return [
            'width' => self::axis($width, 'width'),
            'height' => self::axis($height, 'height'),
        ];
    }

    private static function axis(mixed $value, string $axis): int|float
    {
        if (! is_int($value) && ! is_float($value)) {
            throw new InvalidArgumentException("Scan region {$axis} must be a positive integer (px) or float ratio (<= 1.0).");
        }

        if (is_float($value) && (! is_finite($value) || $value > 1.0)) {
            throw new InvalidArgumentException("Scan region {$axis} ratio cannot exceed 1.0.");
        }

        if ($value <= 0) {
            throw new InvalidArgumentException("Scan region {$axis} must be greater than zero.");
        }

        return $value;
    }
}
