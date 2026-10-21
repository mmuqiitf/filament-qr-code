<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Events;

use Mmuqiitf\FilamentQrCode\Support\ScanResult;

final class ScanAccepted
{
    public function __construct(
        public ScanResult $result,
        public string $field,
        public ?string $statePath = null,
        public ?string $livewireClass = null,
        public ?string $livewireId = null,
        /** The sequence's state path when $field is a step. */
        public ?string $sequence = null,
        /** @var array<string, mixed> the sequence values at the time of the scan */
        public array $values = [],
    ) {}
}
