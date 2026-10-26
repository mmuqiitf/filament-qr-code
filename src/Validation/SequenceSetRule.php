<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

/**
 * Runs validateAllUsing() on submit once every required step has a value.
 * Registered on the last step; reports the failing step's message there.
 */
final readonly class SequenceSetRule implements ValidationRule
{
    /** Submit-time rule that runs a sequence's validateAllUsing() check. */
    public function __construct(
        private QrScanSequence $component,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $values = $this->component->getSequenceState();

        if (! $this->component->allRequiredFilled($values)) {
            return;
        }

        if (($failure = $this->component->findInvalidStep($values)) !== null) {
            $fail($failure[1]);
        }
    }
}
