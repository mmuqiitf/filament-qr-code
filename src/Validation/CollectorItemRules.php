<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * Submit-time check of each collected code against QrCollector::itemRules()
 * and, when enabled, QrCollector::gs1().
 *
 * @internal Not an extension point. Use QrCollector::itemRules().
 */
final readonly class CollectorItemRules implements ValidationRule
{
    public function __construct(
        private QrCollector $collector,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach ($value as $item) {
            $code = $this->codeFrom($item);

            if ($code === null) {
                return;
            }

            $result = $this->collector->checkCollectorItem($code);

            if ($result['passes']) {
                continue;
            }

            $fail($result['message'] ?? __('filament-qr-code::ui.scan_failed'));

            return;
        }
    }

    private function codeFrom(mixed $item): ?string
    {
        if (is_array($item) && array_key_exists('code', $item) && is_scalar($item['code'])) {
            return (string) $item['code'];
        }

        if (is_scalar($item)) {
            return (string) $item;
        }

        return null;
    }
}
