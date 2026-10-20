<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Support;

use Mmuqiitf\FilamentQrCode\Enums\ScanInput;

/** What a ScanCheckStage sees: the code after normalizing and the record the lookup found. */
final readonly class ScanCheckContext
{
    /** @param  array<string, mixed>  $values  the sequence values so far (empty for a QrScanner) */
    public function __construct(
        public string $code,
        public ?ScanInput $source,
        public mixed $record,
        public ?object $component = null,
        public ?ScanStep $step = null,
        public array $values = [],
    ) {}
}
