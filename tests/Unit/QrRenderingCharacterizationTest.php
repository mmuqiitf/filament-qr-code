<?php

declare(strict_types=1);

use BaconQrCode\Common\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Cache;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderer;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderOptions;
use Mmuqiitf\FilamentQrCode\Rendering\QrZipArchiver;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Mmuqiitf\FilamentQrCode\Support\QrZipArchive;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\Legacy\LegacyQrCodeService;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\Legacy\LegacyQrZipArchive;

/*
 * Characterization tests for the P3.2 split: the new rendering classes and the
 * QrCodeService facade must produce byte-identical output to the frozen
 * pre-refactor implementation in tests/Fixtures/Legacy.
 */

beforeEach(function () {
    LegacyQrCodeService::persistentCache(false);
    LegacyQrCodeService::flushRenderCache();
    QrCodeService::persistentCache(false);
    QrCodeService::flushRenderCache();

    $logo = imagecreatetruecolor(64, 64);
    imagefill($logo, 0, 0, (int) imagecolorallocate($logo, 220, 38, 38));
    imagefilledrectangle($logo, 16, 16, 47, 47, (int) imagecolorallocate($logo, 255, 255, 255));
    $this->logoPath = (string) tempnam(sys_get_temp_dir(), 'qr-logo-');
    imagepng($logo, $this->logoPath);
});

afterEach(function () {
    @unlink($this->logoPath);
    QrCodeService::persistentCache(true);
    QrCodeService::flushRenderCache();
});

/**
 * Applies the same fluent configuration to the legacy and the new service.
 */
function configureBoth(Closure $configure, string $data, string $logo = ''): array
{
    return [
        $configure(LegacyQrCodeService::make(), $logo)->generate($data)->getRaw(),
        $configure(QrCodeService::make(), $logo)->generate($data)->getRaw(),
    ];
}

it('QrRenderer renders same SVG bytes', function () {
    $options = new QrRenderOptions(
        data: 'GOLDEN-SVG-001',
        size: 280,
        margin: 3,
        format: QrFormat::Svg,
        foregroundColor: '#0f172a',
        backgroundColor: '#f8fafc',
        errorCorrectionLevel: ErrorCorrectionLevel::M(),
    );

    $golden = (string) file_get_contents(__DIR__.'/../Fixtures/golden/qr-svg-280-m.svg');

    expect((new QrRenderer)->render($options))->toBe($golden);

    [$legacy, $new] = configureBoth(
        fn ($s) => $s->size(280)->margin(3)->color('#0f172a')->backgroundColor('#f8fafc'),
        'GOLDEN-SVG-001',
    );

    expect($new)->toBe($legacy)->toBe($golden);
});

it('renders the same bytes as the pre-split service', function (Closure $configure) {
    [$legacy, $new] = configureBoth($configure, 'CHAR-0042', $this->logoPath);

    expect(pngWithoutTimestamps($new))->toBe(pngWithoutTimestamps($legacy));
})->with([
    'svg defaults' => fn ($s, string $logo) => $s,
    'svg short hex, H level' => fn ($s, string $logo) => $s->color('#123')->backgroundColor('#fed')->errorCorrection('H'),
    'png' => fn ($s, string $logo) => $s->format('png')->size(220)->margin(1),
    'png colored, Q level' => fn ($s, string $logo) => $s->format(QrFormat::Png)->color('#1d4ed8')->errorCorrection('q'),
    'logo' => fn ($s, string $logo) => $s->logo($logo, 60),
    'logo then svg format (coerced to png)' => fn ($s, string $logo) => $s->logo($logo)->format('svg'),
    'missing logo file' => fn ($s, string $logo) => $s->logo('/nonexistent/logo.png'),
    'caption' => fn ($s, string $logo) => $s->size(180)->withText('SKU-42', 12, '#334155'),
    'long caption' => fn ($s, string $logo) => $s->size(180)->withText('Official Product SKU Verification QR'),
    'logo and caption' => fn ($s, string $logo) => $s->logo($logo, 40)->withText('BOTH', 20, '#b91c1c'),
]);

it('keeps the data URI and invalid-colour behaviour', function () {
    $newUri = QrCodeService::make()->format('png')->generate('URI')->toDataUri();
    $legacyUri = LegacyQrCodeService::make()->format('png')->generate('URI')->toDataUri();
    [$newPrefix, $newData] = explode(',', $newUri, 2);
    [$legacyPrefix, $legacyData] = explode(',', $legacyUri, 2);

    expect($newPrefix)->toBe($legacyPrefix)
        ->and(pngWithoutTimestamps((string) base64_decode($newData)))
        ->toBe(pngWithoutTimestamps((string) base64_decode($legacyData)));

    $legacyMessage = null;
    try {
        LegacyQrCodeService::make()->color('#zz')->generate('X');
    } catch (InvalidArgumentException $e) {
        $legacyMessage = $e->getMessage();
    }

    expect(fn () => QrCodeService::make()->color('#zz')->generate('X'))
        ->toThrow(InvalidArgumentException::class, (string) $legacyMessage);
});

it('keeps the persistent cache key format', function () {
    config()->set('cache.default', 'array');
    QrCodeService::persistentCache(true);

    $raw = QrCodeService::make()->size(200)->generate('KEYED')->getRaw();

    $key = 'filament-qr-code:render:'.md5(implode('|', [
        'KEYED', 200, 2, 'svg', '#000000', '#ffffff', ErrorCorrectionLevel::M()->getBits(),
        '', 16, '#000000', '', '', 50,
    ]));

    expect(Cache::get($key))->toBe($raw);
});

/**
 * Imagick stamps PNGs with the wall clock (tIME chunk, date:* tEXt chunks),
 * so two renders a second apart differ only there. Drop exactly those chunks;
 * every other byte must still match. Non-PNG input is returned unchanged.
 */
function pngWithoutTimestamps(string $bytes): string
{
    if (! str_starts_with($bytes, "\x89PNG\r\n\x1a\n")) {
        return $bytes;
    }

    $out = substr($bytes, 0, 8);
    $at = 8;
    while ($at + 8 <= strlen($bytes)) {
        $length = (int) unpack('N', substr($bytes, $at, 4))[1];
        $type = substr($bytes, $at + 4, 4);
        $chunk = substr($bytes, $at, 12 + $length);
        $at += 12 + $length;

        $isTimestamp = $type === 'tIME'
            || (in_array($type, ['tEXt', 'zTXt', 'iTXt'], true) && str_starts_with(substr($chunk, 8), 'date:'));

        if (! $isTimestamp) {
            $out .= $chunk;
        }
    }

    return $out;
}

/**
 * Zero the DOS mod-time/mod-date fields in local and central headers;
 * ZipArchive stamps the current time, so only those bytes may differ.
 */
function zipWithoutTimestamps(string $zip): string
{
    foreach (["PK\x03\x04" => 10, "PK\x01\x02" => 12] as $signature => $offset) {
        $at = 0;
        while (($at = strpos($zip, $signature, $at)) !== false) {
            $zip = substr_replace($zip, "\0\0\0\0", $at + $offset, 4);
            $at += 4;
        }
    }

    return $zip;
}

it('packs the same zip bytes as the pre-split archive', function () {
    $files = [
        'a.svg' => QrCodeService::make()->generate('ZIP-A')->getRaw(),
        'b.png' => QrCodeService::make()->format('png')->generate('ZIP-B')->getRaw(),
        'nested name.svg' => '<svg/>',
    ];

    $legacy = zipWithoutTimestamps(LegacyQrZipArchive::fromFiles($files));

    expect(zipWithoutTimestamps((new QrZipArchiver)->pack($files)))->toBe($legacy)
        ->and(zipWithoutTimestamps(QrZipArchive::fromFiles($files)))->toBe($legacy);
});
