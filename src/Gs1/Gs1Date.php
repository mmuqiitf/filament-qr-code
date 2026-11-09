<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

use Carbon\CarbonImmutable;

/**
 * Calendar dates in GS1 fields, for the Syntax Dictionary linters
 * `yymmd0`, `yymmdd` and `yyyymmdd`.
 *
 * A two-digit year uses the GS1 General Specifications sliding window
 * (section 7.12): 51 to 99 years ahead of the current year is the previous
 * century, 50 to 99 years behind is the next century, anything else is the
 * current century.
 *
 * @internal Not an extension point. Use Element::date() and ElementString::date().
 */
final class Gs1Date
{
    public const LINTERS = ['yymmd0', 'yymmdd', 'yyyymmdd'];

    /**
     * The date `$digits` stands for under `$linter`, at the start of the day,
     * or null when it is not on the calendar.
     */
    public static function resolve(string $linter, string $digits): ?CarbonImmutable
    {
        $expected = $linter === 'yyyymmdd' ? 8 : 6;

        if (strlen($digits) !== $expected || ctype_digit($digits) !== true) {
            return null;
        }

        $year = $expected === 8
            ? (int) substr($digits, 0, 4)
            : self::year((int) substr($digits, 0, 2));
        $month = (int) substr($digits, -4, 2);
        $day = (int) substr($digits, -2);

        if ($month < 1 || $month > 12) {
            return null;
        }

        if ($day === 0 && $linter === 'yymmd0') {
            return CarbonImmutable::create($year, $month, 1)?->endOfMonth()->startOfDay();
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::create($year, $month, $day);
    }

    private static function year(int $yy): int
    {
        $current = CarbonImmutable::now()->year;
        $century = intdiv($current, 100) * 100;
        $difference = $yy - ($current % 100);

        if ($difference >= 51) {
            return $century - 100 + $yy;
        }

        if ($difference <= -50) {
            return $century + 100 + $yy;
        }

        return $century + $yy;
    }
}
