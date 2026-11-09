<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * A GS1 element string could not be parsed or built: unknown application
 * identifier, wrong length, a character outside the AI's set, a wrong check
 * digit, a date that is not on the calendar, or a duplicate.
 *
 * `kind` says which (one of the constants below) and `ai` names the
 * application identifier when one is known, so callers can word their own
 * message.
 */
class InvalidGs1Exception extends InvalidArgumentException
{
    public const EMPTY = 'empty';

    public const SYNTAX = 'syntax';

    public const UNKNOWN_AI = 'unknown_ai';

    public const DUPLICATE_AI = 'duplicate_ai';

    public const LENGTH = 'length';

    public const CHARACTER_SET = 'character_set';

    public const FORMAT = 'format';

    public const CHECK_DIGIT = 'check_digit';

    public const DATE = 'date';

    /** Failure for `$value`, with a reason safe to show next to a form field. */
    public function __construct(
        public readonly string $value,
        public readonly string $reason,
        ?Throwable $previous = null,
        public readonly string $kind = self::SYNTAX,
        public readonly ?string $ai = null,
    ) {
        parent::__construct("Invalid GS1 element string [{$value}]: {$reason}.", 0, $previous);
    }
}
