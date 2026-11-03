<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Exceptions;

use InvalidArgumentException;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Throwable;

/**
 * The value can't be encoded in the requested barcode type, e.g. an EAN-13
 * with a wrong check digit or a Code 39 value with lowercase letters.
 */
class InvalidBarcodeException extends InvalidArgumentException
{
    public function __construct(
        public readonly BarcodeType $type,
        public readonly string $value,
        public readonly string $reason,
        ?Throwable $previous = null,
    ) {
        parent::__construct("Invalid {$type->getLabel()} value [{$value}]: {$reason}.", 0, $previous);
    }
}
