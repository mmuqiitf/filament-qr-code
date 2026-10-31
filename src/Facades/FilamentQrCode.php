<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Facades;

use Illuminate\Support\Facades\Facade;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

/**
 * Static entry point to QrCodeService. Start every QR with make(): the facade root is cached, so make() is what gives each code its own builder.
 *
 * @method static QrCodeService make()
 * @method static QrCodeService size(int $size)
 * @method static QrCodeService margin(int $margin)
 * @method static QrCodeService format(\Mmuqiitf\FilamentQrCode\Enums\QrFormat|string $format)
 * @method static QrCodeService color(string $hexColor)
 * @method static QrCodeService backgroundColor(string $hexColor)
 * @method static QrCodeService errorCorrection(string|\BaconQrCode\Common\ErrorCorrectionLevel $level)
 * @method static QrCodeService withText(string $text, int $fontSize = 16, string $fontColor = '#000000', ?string $fontPath = null)
 * @method static QrCodeService font(string $fontPath)
 * @method static QrCodeService logo(string $path, int $size = 50)
 * @method static QrCodeService fileName(string $fileName)
 * @method static QrCodeService generate(string $data)
 * @method static string getRaw()
 * @method static string toDataUri()
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse download(?string $fileName = null)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(?string $fileName = null)
 * @method static void persistentCache(bool $enabled = true)
 * @method static void cacheTtl(int|false|null $seconds)
 * @method static void cacheStore(?string $store)
 * @method static int|false|null effectiveCacheTtl()
 * @method static void flushRenderCache()
 * @method static void flushPersistentCache()
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
