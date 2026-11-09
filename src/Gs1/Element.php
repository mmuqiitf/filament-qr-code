<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

use Carbon\CarbonImmutable;

/**
 * One application identifier and its value from a GS1 element string.
 * `value` is always the raw string; `date()` and `decimal()` read it.
 */
final readonly class Element
{
    /** @internal Constructed by Gs1::parse() and Gs1::build(). */
    public function __construct(
        public string $ai,
        public string $value,
        public string $title,
    ) {}

    /**
     * The first date in the value at the start of the day, or null when the
     * AI carries no date. Day `00` is the last day of the month. A two-digit
     * year uses the GS1 sliding window. Times are ignored.
     */
    public function date(): ?CarbonImmutable
    {
        return SyntaxTable::find($this->ai)?->date($this->value);
    }

    /**
     * The value as an exact decimal string, such as `'1.250'` for
     * `(3103)001250`, or null when the AI has no implied decimal point.
     * Applies to 310n to 369n and 390n to 395n; the currency code of
     * 391n and 393n is left out.
     */
    public function decimal(): ?string
    {
        return SyntaxTable::find($this->ai)?->decimal($this->value);
    }
}
