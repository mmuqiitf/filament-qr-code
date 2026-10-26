<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

final readonly class ScanChecksRule implements ValidationRule
{
    /** Submit-time rule that runs a field's server scan checks. */
    public function __construct(
        private QrScanner $component,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (! is_scalar($value)) {
            $fail(__('filament-qr-code::ui.scan_rejected'));

            return;
        }

        $result = $this->component->runScanChecks($this->component->normalizeScannedCode((string) $value), source: null);

        if ($result->passes) {
            return;
        }

        $fail((string) $result->message);
    }
}
