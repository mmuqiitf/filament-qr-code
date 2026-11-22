<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

final readonly class SequenceStepRule implements ValidationRule
{
    /** Submit-time rule that runs one sequence step's checks. */
    public function __construct(
        private QrScanSequence $component,
        private ScanStep $step,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $codes = is_array($value) && $this->step->isRepeating() ? $value : [$value];

        $state = $this->component->getSequenceState();

        // The count is re-read from live state, so it matches the browser's k / n.
        $limit = $this->step->isRepeating() ? $this->component->getRepeatLimit($this->step, $state) : null;

        if ($limit !== null && count($codes) !== $limit) {
            $fail(__(count($codes) > $limit ? 'filament-qr-code::ui.repeat_too_many' : 'filament-qr-code::ui.repeat_needs_codes', ['count' => $limit]));

            return;
        }

        foreach (array_values($codes) as $position => $code) {
            if (! is_scalar($code)) {
                $fail(__('filament-qr-code::ui.scan_rejected'));

                return;
            }

            $result = $this->component->checkStep(
                $this->step,
                trim((string) $code),
                null,
                $this->component->valuesBeforeStep($this->step, $state, $this->step->isRepeating() ? $position : null),
            );

            if (! $result->passes) {
                $fail((string) $result->message);

                return;
            }
        }
    }
}
