<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DistinctCodes implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $codes = [];

        foreach ($value as $item) {
            if (is_array($item) && array_key_exists('code', $item) && is_scalar($item['code'])) {
                $codes[] = (string) $item['code'];
            } elseif (is_scalar($item)) {
                $codes[] = (string) $item;
            } else {
                return;
            }
        }

        if (count($codes) !== count(array_unique($codes))) {
            $fail(__('filament-qr-code::ui.duplicate_codes'));
        }
    }
}
