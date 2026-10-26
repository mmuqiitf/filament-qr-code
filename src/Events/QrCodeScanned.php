<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Events;

class QrCodeScanned
{
    /** Dispatched for every server-observed scan. */
    public function __construct(
        public string $code,
        public string $source,
        public ?string $field = null,
        /** @var array<string, mixed> */
        public array $context = [],
    ) {}
}
