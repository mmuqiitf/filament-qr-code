<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

final readonly class SequenceStepRule implements ValidationRule
{
    public function __construct(
        private QrScanSequence $component,
        private ScanStep $step,
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

        $result = $this->component->checkStep($this->step, trim((string) $value), null, $this->component->getSequenceState());

        if (! $result->passes) {
            $fail((string) $result->message);
        }
    }
}
