<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Gs1;

/**
 * One application identifier and its value from a GS1 element string.
 */
final readonly class Element
{
    /** @internal Constructed by Gs1::parse() and Gs1::build(). */
    public function __construct(
        public string $ai,
        public string $value,
        public string $title,
    ) {}
}
