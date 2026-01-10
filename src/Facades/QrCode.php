<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getConfiguration()
 * @method static string getDefaultCameraFacing()
 * @method static string getDefaultScanMode()
 *
 * @see \Mmuqiitf\FilamentQrCode\QrCodeManager
 */
class QrCode extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filament-qr-code';
    }
}
