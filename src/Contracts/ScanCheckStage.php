<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Contracts;

use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;

/**
 * A custom check added to a field with ->pipe(). Runs after the built-in
 * rules, lookup and closures.
 */
interface ScanCheckStage
{
    /**
     * Return null or true to pass, false to reject with the default message,
     * or a string to reject with that message.
     */
    public function check(ScanCheckContext $context): bool|string|null;
}
