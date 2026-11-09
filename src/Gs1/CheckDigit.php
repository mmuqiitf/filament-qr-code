<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

use InvalidArgumentException;

/**
 * GS1 mod-10 check digit used by GTIN, SSCC, GLN, GDTI, GSRN and by
 * EAN-13, EAN-8, UPC-A and ITF-14. The GMN alphanumeric check character
 * (AI 8013) is a different algorithm.
 */
final class CheckDigit
{
    /**
     * Check digit for the digits that precede it.
     *
     * @throws InvalidArgumentException When $digits is empty or not all digits.
     */
    public static function calculate(string $digits): int
    {
        if ($digits === '' || ctype_digit($digits) !== true) {
            throw new InvalidArgumentException('GS1 check digit input must be one or more digits.');
        }

        $sum = 0;

        foreach (array_reverse(str_split($digits)) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 3 : 1);
        }

        return (10 - ($sum % 10)) % 10;
    }

    /**
     * Whether $value ends with the check digit for the digits before it.
     */
    public static function validate(string $value): bool
    {
        if (strlen($value) < 2 || ctype_digit($value) !== true) {
            return false;
        }

        return (int) substr($value, -1) === self::calculate(substr($value, 0, -1));
    }
}
