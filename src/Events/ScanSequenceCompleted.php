<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ScanSequenceCompleted
{
    use Dispatchable;

    /** @param  array<string, mixed>  $values */
    public function __construct(
        public array $values,
        public ?string $statePath = null,
    ) {}
}
