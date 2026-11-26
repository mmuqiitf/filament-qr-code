<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * Submit-time check of QrCollector::maxItems().
 *
 * @internal Not an extension point. Use QrCollector::maxItems().
 */
final readonly class CollectorMaxItems implements ValidationRule
{
    public function __construct(
        private QrCollector $collector,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $max = $this->collector->getMaxItems();

        if ($max === null || ! is_array($value)) {
            return;
        }

        if (count($value) > $max) {
            $fail((string) __('filament-qr-code::ui.collector_max_items', ['max' => $max]));
        }
    }
}
