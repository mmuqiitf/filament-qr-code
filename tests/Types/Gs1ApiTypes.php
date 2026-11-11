<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Types;

use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/**
 * Analysed by PHPStan (level 9, see phpstan.neon.dist), never run: an app
 * writing `gs1()` maps the natural way must pass. PHP turns keys such as
 * '10', '17' and '3103' into ints, so the fill map is array<int|string, string>.
 */
final class Gs1ApiTypes
{
    public function scanner(): QrScanner
    {
        return QrScanner::make('label')->gs1(
            fill: ['01' => 'gtin', '10' => 'batch', '17' => 'expires_at', '3103' => 'net_weight_kg'],
            require: ['01', '17', 10],
            keepMissing: true,
        );
    }

    public function listener(): QrHardwareScannerListener
    {
        return QrHardwareScannerListener::make()->gs1(
            fill: ['01' => 'gtin', '10' => 'batch', '21' => 'serial'],
            require: ['01'],
            store: 'label',
        );
    }
}
