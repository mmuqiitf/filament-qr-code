<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Gs1\CheckDigit;

/**
 * The value is digits ending in the GS1 mod-10 check digit. Pass the number
 * itself (a GTIN, SSCC or GLN), not a whole GS1 element string.
 */
final readonly class ValidCheckDigit implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && CheckDigit::validate($value)) {
            return;
        }

        $fail(__('filament-qr-code::ui.invalid_check_digit'));
    }
}
