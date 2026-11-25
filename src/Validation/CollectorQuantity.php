<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * Submit-time shape and positive-quantity check when QrCollector::quantity() is enabled.
 *
 * @internal Not an extension point. Use QrCollector::quantity().
 */
final readonly class CollectorQuantity implements ValidationRule
{
    public function __construct(
        private QrCollector $collector,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->collector->hasQuantity() || ! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            if (! is_array($item) || ! array_key_exists('code', $item) || ! is_scalar($item['code']) || trim((string) $item['code']) === '') {
                $fail((string) __('filament-qr-code::ui.collector_quantity_invalid'));

                return;
            }

            $qty = $item['qty'] ?? null;

            if (! is_int($qty) && (! is_string($qty) || preg_match('/^\d+$/', $qty) !== 1)) {
                $fail((string) __('filament-qr-code::ui.collector_quantity_invalid'));

                return;
            }

            if ((int) $qty < 1) {
                $fail((string) __('filament-qr-code::ui.collector_quantity_invalid'));

                return;
            }
        }
    }
}
