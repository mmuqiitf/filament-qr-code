<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ScanSequenceCompleted
{
    use Dispatchable;

    /**
     * Dispatched when a sequence has every required step.
     *
     * @param  array<string, mixed>  $values
     */
    public function __construct(
        public array $values,
        public ?string $statePath = null,
        public ?string $livewireClass = null,
        public ?string $livewireId = null,
    ) {}
}
