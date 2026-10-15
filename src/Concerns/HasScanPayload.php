<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Mmuqiitf\FilamentQrCode\Support\ScanOptions;

trait HasScanPayload
{
    /**
     * One payload for the browser: every scan option this surface supports,
     * with config-backed defaults. Blade views spread it into the Alpine init.
     *
     * @return array<string, mixed>
     */
    public function getScanPayload(): array
    {
        return ScanOptions::payload($this);
    }

    /**
     * Surface-specific options merged on top of the trait-contributed ones.
     *
     * @return array<string, mixed>
     */
    public function getComponentScanPayload(): array
    {
        return [];
    }
}
