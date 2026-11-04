<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Rendering\QrLogoCompositor;
use Mmuqiitf\FilamentQrCode\Rendering\RenderCacheKey;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

it('fits a wide logo inside the box without stretching it', function () {
    expect(QrLogoCompositor::fittedSize(40, 10, 20))->toBe([20, 5])
        ->and(QrLogoCompositor::fittedSize(10, 40, 20))->toBe([5, 20]);
});

it('draws a wide logo shorter than a square on the GD path', function () {
    $canvas = imagecreatetruecolor(80, 80);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    ob_start();
    imagepng($canvas);
    $qr = (string) ob_get_clean();
    imagedestroy($canvas);

    $logo = imagecreatetruecolor(40, 10);
    $red = imagecolorallocate($logo, 255, 0, 0);
    imagefill($logo, 0, 0, $red);
    $path = tempnam(sys_get_temp_dir(), 'qr-logo-');
    imagepng($logo, $path);
    imagedestroy($logo);

    $png = (new QrLogoCompositor)->applyGd($qr, (string) $path, 40);
    unlink((string) $path);

    $image = imagecreatefromstring($png);
    expect($image)->not->toBeFalse();

    $minX = 80;
    $maxX = 0;
    $minY = 80;
    $maxY = 0;

    for ($y = 0; $y < 80; $y++) {
        for ($x = 0; $x < 80; $x++) {
            $rgb = imagecolorat($image, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            if ($r > 200 && $g < 40 && $b < 40) {
                $minX = min($minX, $x);
                $maxX = max($maxX, $x);
                $minY = min($minY, $y);
                $maxY = max($maxY, $y);
            }
        }
    }

    imagedestroy($image);

    $width = $maxX - $minX + 1;
    $height = $maxY - $minY + 1;

    expect($width)->toBeGreaterThan($height * 2);
});

it('misses the render cache when a logo file is replaced', function () {
    $source = (string) realpath(__DIR__.'/../Fixtures/logos/inside/logo.png');
    $path = tempnam(sys_get_temp_dir(), 'qr-logo-cache-');
    copy($source, (string) $path);
    touch((string) $path, time() - 10);

    QrCodeService::cacheTtl(null);
    QrCodeService::cacheStore(null);
    QrCodeService::persistentCache(true);
    QrCodeService::flushRenderCache();
    QrCodeService::flushPersistentCache();

    $first = QrCodeService::make()->logo((string) $path, 24)->generate('LOGO-MTIME')->getRaw();

    $replacement = imagecreatetruecolor(8, 8);
    $red = imagecolorallocate($replacement, 255, 0, 0);
    imagefill($replacement, 0, 0, $red);
    imagepng($replacement, (string) $path);
    imagedestroy($replacement);
    touch((string) $path, time() + 10);
    clearstatcache(true, (string) $path);

    $second = QrCodeService::make()->logo((string) $path, 24)->generate('LOGO-MTIME')->getRaw();
    unlink((string) $path);

    expect($second)->not->toBe($first)
        ->and(RenderCacheKey::fileToken($source))->not->toBeNull();
});
