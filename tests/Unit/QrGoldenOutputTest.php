<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

/*
 * Golden-output tests: lock the exact QR output of 1.0.0 so later refactors
 * (e.g. the shared barcode renderer) cannot change it silently.
 *
 * SVG output is backend independent and compared byte for byte.
 * PNG output depends on the raster backend (Imagick when loaded, else GD),
 * so PNG goldens are stored per backend as a fingerprint of the decoded
 * pixels (dimensions + sha256 of the RGBA buffer), never of the PNG bytes.
 *
 * Regenerate deliberately with: QR_GOLDEN_UPDATE=1 composer test -- --filter=QrGoldenOutput
 */

beforeEach(function () {
    QrCodeService::persistentCache(false);
    QrCodeService::flushRenderCache();
});

afterEach(function () {
    QrCodeService::persistentCache(true);
    QrCodeService::flushRenderCache();
});

function goldenLogoPath(): string
{
    return __DIR__.'/../Fixtures/golden/logo.png';
}

/** @return array<string, Closure(QrCodeService): QrCodeService> */
function goldenSvgCases(): array
{
    return [
        'ec L' => fn (QrCodeService $s) => $s->errorCorrection('L'),
        'ec M' => fn (QrCodeService $s) => $s->errorCorrection('M'),
        'ec Q' => fn (QrCodeService $s) => $s->errorCorrection('Q'),
        'ec H' => fn (QrCodeService $s) => $s->errorCorrection('H'),
        'size 120' => fn (QrCodeService $s) => $s->size(120),
        'size 300' => fn (QrCodeService $s) => $s->size(300),
        'size 600' => fn (QrCodeService $s) => $s->size(600),
        'margin 0' => fn (QrCodeService $s) => $s->margin(0),
        'margin 10' => fn (QrCodeService $s) => $s->margin(10),
        'colours' => fn (QrCodeService $s) => $s->color('#1d4ed8')->backgroundColor('#fef3c7'),
        'short hex colours' => fn (QrCodeService $s) => $s->color('#123')->backgroundColor('#fed'),
    ];
}

/** @return array<string, Closure(QrCodeService): QrCodeService> */
function goldenPngCases(): array
{
    return [
        'png defaults' => fn (QrCodeService $s) => $s->format('png'),
        'png ec H size 240' => fn (QrCodeService $s) => $s->format('png')->errorCorrection('H')->size(240),
        'png margin 0' => fn (QrCodeService $s) => $s->format('png')->margin(0),
        'png colours' => fn (QrCodeService $s) => $s->format('png')->color('#1d4ed8')->backgroundColor('#fef3c7'),
        'png logo' => fn (QrCodeService $s) => $s->logo(goldenLogoPath(), 60),
        'png logo small' => fn (QrCodeService $s) => $s->logo(goldenLogoPath(), 30)->errorCorrection('H'),
    ];
}

function goldenSlug(string $case): string
{
    return (string) preg_replace('/[^a-z0-9]+/', '-', strtolower($case));
}

function goldenBackend(): string
{
    return extension_loaded('imagick') ? 'imagick' : 'gd';
}

/** @return array{width: int, height: int, sha256: string} */
function pngFingerprint(string $png): array
{
    $image = imagecreatefromstring($png);
    expect($image)->not->toBeFalse();
    assert($image instanceof GdImage);

    $width = imagesx($image);
    $height = imagesy($image);
    $pixels = '';
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            $pixels .= pack('C4', $rgba['red'], $rgba['green'], $rgba['blue'], $rgba['alpha']);
        }
    }

    return ['width' => $width, 'height' => $height, 'sha256' => hash('sha256', $pixels)];
}

function goldenUpdating(): bool
{
    return getenv('QR_GOLDEN_UPDATE') === '1';
}

it('keeps the SVG output byte for byte', function (string $case) {
    $svg = goldenSvgCases()[$case](QrCodeService::make())->generate('GOLDEN-1.0.0')->getRaw();
    $fixture = __DIR__.'/../Fixtures/golden/svg/'.goldenSlug($case).'.svg';

    if (goldenUpdating()) {
        @mkdir(dirname($fixture), 0777, true);
        file_put_contents($fixture, $svg);
    }

    expect($fixture)->toBeFile()
        ->and($svg)->toBe(file_get_contents($fixture));
})->with(array_keys(goldenSvgCases()));

it('keeps the PNG pixels for the current raster backend', function (string $case) {
    $png = goldenPngCases()[$case](QrCodeService::make())->generate('GOLDEN-1.0.0')->getRaw();
    $fixture = __DIR__.'/../Fixtures/golden/png/'.goldenBackend().'.json';
    $all = is_file($fixture) ? (array) json_decode((string) file_get_contents($fixture), true) : [];

    if (goldenUpdating()) {
        $all[goldenSlug($case)] = pngFingerprint($png);
        ksort($all);
        file_put_contents($fixture, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    $actual = pngFingerprint($png);
    $hint = sprintf('%s golden "%s" mismatch; actual: %s', goldenBackend(), goldenSlug($case), json_encode($actual));

    expect($all)->toHaveKey(goldenSlug($case), message: $hint)
        ->and($actual)->toBe($all[goldenSlug($case)], $hint);
})->with(array_keys(goldenPngCases()));
