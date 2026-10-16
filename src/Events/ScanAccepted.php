<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Events;

use Mmuqiitf\FilamentQrCode\Support\ScanResult;

final class ScanAccepted
{
    public function __construct(
        public ScanResult $result,
        public string $field,
    ) {}
}
