<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * A GS1 element string could not be parsed or built: unknown application
 * identifier, wrong length, a character outside the AI's set, or a duplicate.
 */
class InvalidGs1Exception extends InvalidArgumentException
{
    /** Failure for `$value`, with a reason safe to show next to a form field. */
    public function __construct(
        public readonly string $value,
        public readonly string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct("Invalid GS1 element string [{$value}]: {$reason}.", 0, $previous);
    }
}
