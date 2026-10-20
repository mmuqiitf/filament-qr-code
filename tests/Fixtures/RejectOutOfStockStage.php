<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;

/** Test stage: rejects a product without stock; records every call. */
class RejectOutOfStockStage implements ScanCheckStage
{
    /** @var list<ScanCheckContext> */
    public static array $seen = [];

    public function __construct(public StageMessages $messages) {}

    public function check(ScanCheckContext $context): bool|string|null
    {
        self::$seen[] = $context;

        if ($context->record instanceof ScanCheckProduct && $context->record->stock === 0) {
            return $this->messages->outOfStock;
        }

        return null;
    }
}
