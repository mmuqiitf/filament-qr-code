<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

/*
 * Golden-output tests: lock the exact QR output of 1.0.0 so later refactors
 * (e.g. the shared barcode renderer) cannot change it silently.
 *
 * SVG output is backend independent and compared byte for byte.
 * PNG output depends on the raster backend (Imagick when loaded, else GD),
 * so PNG goldens are stored per backend as exact dimensions plus a
 * downsampled colour grid of the decoded pixels, compared with a small
 * per-cell tolerance. That catches flipped modules, colour, size, margin and
 * logo changes while ignoring anti-aliasing differences between
 * ImageMagick/GD builds (an exact pixel hash broke on every other build).
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

/**
 * Cells per side of the downsampled colour grid stored for each PNG golden.
 * At 300px a cell is ~6px, smaller than a QR module, so a flipped module or a
 * colour change moves whole cells while anti-aliasing seams barely move one.
 */
const GOLDEN_PNG_GRID = 48;

/** Largest per-channel difference (0-255) a grid cell may drift between raster builds. */
const GOLDEN_PNG_TOLERANCE = 24;

/** @return array{width: int, height: int, grid: string} */
function pngFingerprint(string $png): array
{
    $image = imagecreatefromstring($png);
    expect($image)->not->toBeFalse();
    assert($image instanceof GdImage);

    $width = imagesx($image);
    $height = imagesy($image);
    $grid = '';
    for ($cy = 0; $cy < GOLDEN_PNG_GRID; $cy++) {
        for ($cx = 0; $cx < GOLDEN_PNG_GRID; $cx++) {
            [$x0, $x1] = [intdiv($cx * $width, GOLDEN_PNG_GRID), max(intdiv(($cx + 1) * $width, GOLDEN_PNG_GRID), intdiv($cx * $width, GOLDEN_PNG_GRID) + 1)];
            [$y0, $y1] = [intdiv($cy * $height, GOLDEN_PNG_GRID), max(intdiv(($cy + 1) * $height, GOLDEN_PNG_GRID), intdiv($cy * $height, GOLDEN_PNG_GRID) + 1)];
            $sum = [0, 0, 0];
            $count = 0;
            for ($y = $y0; $y < min($y1, $height); $y++) {
                for ($x = $x0; $x < min($x1, $width); $x++) {
                    $rgba = imagecolorsforindex($image, imagecolorat($image, $x, $y));
                    // Composite onto white so transparent and opaque-white backgrounds compare equal.
                    $alpha = 1 - $rgba['alpha'] / 127;
                    $sum[0] += $rgba['red'] * $alpha + 255 * (1 - $alpha);
                    $sum[1] += $rgba['green'] * $alpha + 255 * (1 - $alpha);
                    $sum[2] += $rgba['blue'] * $alpha + 255 * (1 - $alpha);
                    $count++;
                }
            }
            $grid .= pack('C3', ...array_map(fn (float|int $v): int => (int) round($v / max($count, 1)), $sum));
        }
    }

    return ['width' => $width, 'height' => $height, 'grid' => base64_encode($grid)];
}

/**
 * Compare a PNG fingerprint with its golden: dimensions must match exactly and
 * every grid cell must stay within GOLDEN_PNG_TOLERANCE per channel.
 *
 * @param  array<string, mixed>  $expected
 * @param  array{width: int, height: int, grid: string}  $actual
 * @return list<string>
 */
function pngGoldenProblems(array $expected, array $actual): array
{
    if ([$expected['width'] ?? null, $expected['height'] ?? null] !== [$actual['width'], $actual['height']]) {
        return [sprintf('size %sx%s, golden %sx%s', $actual['width'], $actual['height'], $expected['width'] ?? '?', $expected['height'] ?? '?')];
    }

    $want = base64_decode((string) ($expected['grid'] ?? ''), true);
    $got = base64_decode($actual['grid'], true);
    if ($want === false || strlen($want) !== strlen((string) $got)) {
        return ['golden has no grid in the current format; regenerate with QR_GOLDEN_UPDATE=1'];
    }

    $problems = [];
    for ($i = 0, $n = strlen($want); $i < $n; $i++) {
        $delta = abs(ord($want[$i]) - ord($got[$i]));
        if ($delta > GOLDEN_PNG_TOLERANCE) {
            $cell = intdiv($i, 3);
            $problems[] = sprintf('cell (%d,%d) %s off by %d', $cell % GOLDEN_PNG_GRID, intdiv($cell, GOLDEN_PNG_GRID), 'RGB'[$i % 3], $delta);
        }
    }

    return $problems;
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

    expect($all)->toHaveKey(goldenSlug($case), message: goldenBackend().' golden "'.goldenSlug($case).'" missing; regenerate with QR_GOLDEN_UPDATE=1');

    $problems = pngGoldenProblems((array) $all[goldenSlug($case)], pngFingerprint($png));

    expect($problems)->toBe([], sprintf('%s golden "%s" drifted: %s', goldenBackend(), goldenSlug($case), implode('; ', array_slice($problems, 0, 5))));
})->with(array_keys(goldenPngCases()));

function goldenTestPng(Closure $paint): string
{
    $image = imagecreatetruecolor(300, 300);
    imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
    $paint($image);
    ob_start();
    imagepng($image);

    return (string) ob_get_clean();
}

it('ignores tiny raster differences between PNG builds', function () {
    $base = goldenTestPng(fn (GdImage $i) => imagefilledrectangle($i, 40, 40, 59, 59, imagecolorallocate($i, 0, 0, 0)));
    $jitter = goldenTestPng(function (GdImage $i) {
        imagefilledrectangle($i, 40, 40, 59, 59, imagecolorallocate($i, 0, 0, 0));
        imagesetpixel($i, 60, 50, imagecolorallocate($i, 120, 120, 120)); // anti-aliasing seam
        imagesetpixel($i, 200, 200, imagecolorallocate($i, 250, 250, 250));
    });

    expect(pngGoldenProblems(pngFingerprint($base), pngFingerprint($jitter)))->toBe([]);
});

it('catches a flipped QR module, a colour change and a size change', function () {
    $base = pngFingerprint(goldenTestPng(fn (GdImage $i) => imagefilledrectangle($i, 40, 40, 49, 49, imagecolorallocate($i, 0, 0, 0))));
    $flipped = pngFingerprint(goldenTestPng(fn (GdImage $i) => null));
    $recoloured = pngFingerprint(goldenTestPng(fn (GdImage $i) => imagefilledrectangle($i, 40, 40, 49, 49, imagecolorallocate($i, 29, 78, 216))));
    $resized = $base;
    $resized['width'] = 240;

    expect(pngGoldenProblems($base, $flipped))->not->toBe([])
        ->and(pngGoldenProblems($base, $recoloured))->not->toBe([])
        ->and(pngGoldenProblems($base, $resized))->not->toBe([]);
});
