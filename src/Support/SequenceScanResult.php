<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

/**
 * @internal Not covered by semver; may change in any release.
 */
final readonly class SequenceScanResult
{
    /**
     * @param  list<string>  $skipped
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public ScanResult $scan,
        public ?string $step,
        public ?string $nextStep,
        public array $values,
        public array $skipped = [],
        public bool $completed = false,
        public ?string $invalidStep = null,
        public ?bool $submitted = null,
        public bool $reset = false,
        public bool $finished = false,
    ) {}

    /**
     * @return array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed, step: ?string, skipped: list<string>, next_step: ?string, completed: bool, invalid_step: ?string, submitted: ?bool, reset: bool, finished: bool, values: array<string, mixed>}
     */
    public function toClient(): array
    {
        return [
            ...$this->scan->toClient(),
            'step' => $this->step,
            'skipped' => $this->skipped,
            'next_step' => $this->nextStep,
            'completed' => $this->completed,
            'invalid_step' => $this->invalidStep,
            'submitted' => $this->submitted,
            'reset' => $this->reset,
            'finished' => $this->finished,
            'values' => $this->values,
        ];
    }
}
