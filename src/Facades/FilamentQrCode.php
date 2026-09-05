<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Facades;

use Illuminate\Support\Facades\Facade;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

/**
 * @method static QrCodeService make()
 * @method static QrCodeService generate(string $data)
 *
 * @see QrCodeService
 */
class FilamentQrCode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QrCodeService::class;
    }
}
