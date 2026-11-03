<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeRenderer;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeRenderOptions;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

/*
 * Golden-output tests for the 1.1 barcodes. Every fixture was checked with a
 * barcode scanner library (zxing-cpp) when it was first written.
 *
 * SVG is compared byte for byte. PNG bars are axis-aligned rectangles with no
 * anti-aliasing, so the PNG golden is the exact bar pattern of the middle
 * pixel row plus the image size, identical for GD and Imagick.
 *
 * Regenerate deliberately with: QR_GOLDEN_UPDATE=1 composer test -- --filter=BarcodeGoldenOutput
 */

beforeEach(function () {
    QrCodeService::persistentCache(false);
    BarcodeService::flushRenderCache();
});

afterEach(function () {
    QrCodeService::persistentCache(true);
    BarcodeService::flushRenderCache();
});

/** @return array<string, array{BarcodeType, string}> */
function barcodeGoldenValues(): array
{
    return [
        'code128' => [BarcodeType::Code128, 'Hello 123'],
        'ean13' => [BarcodeType::Ean13, '4006381333931'],
        'ean8' => [BarcodeType::Ean8, '96385074'],
        'upca' => [BarcodeType::UpcA, '036000291452'],
        'code39' => [BarcodeType::Code39, 'HELLO-1'],
        'itf14' => [BarcodeType::Itf14, '00012345678905'],
    ];
}

/** @return array<string, Closure(BarcodeService): BarcodeService> */
function barcodeGoldenSvgOptions(): array
{
    return [
        'defaults' => fn (BarcodeService $s) => $s,
        'no text' => fn (BarcodeService $s) => $s->showText(false),
        'bar width 3 height 40 margin 2' => fn (BarcodeService $s) => $s->barWidth(3)->height(40)->margin(2),
        'colours' => fn (BarcodeService $s) => $s->color('#1d4ed8')->backgroundColor('#fef3c7'),
    ];
}

/** @return array<string, array{string, string}> */
function barcodeGoldenSvgCases(): array
{
    $cases = [];
    foreach (array_keys(barcodeGoldenValues()) as $type) {
        $cases[$type] = [$type, 'defaults'];
    }
    foreach (array_keys(barcodeGoldenSvgOptions()) as $option) {
        $cases["ean13 {$option}"] = ['ean13', $option];
    }

    return $cases;
}

function barcodeGoldenSlug(string $case): string
{
    return (string) preg_replace('/[^a-z0-9]+/', '-', strtolower($case));
}

/** @return array{width: int, height: int, row: string} */
function barcodePngFingerprint(string $png): array
{
    $image = imagecreatefromstring($png);
    assert($image instanceof GdImage);

    $y = intdiv(imagesy($image), 2);
    $row = '';
    for ($x = 0; $x < imagesx($image); $x++) {
        $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));
        $row .= ($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3 < 128 ? '1' : '0';
    }

    return ['width' => imagesx($image), 'height' => imagesy($image), 'row' => $row];
}

it('keeps the barcode SVG output byte for byte', function (string $case) {
    [$typeKey, $option] = barcodeGoldenSvgCases()[$case];
    [$type, $value] = barcodeGoldenValues()[$typeKey];
    $svg = barcodeGoldenSvgOptions()[$option](BarcodeService::make()->type($type))->generate($value)->getRaw();
    $fixture = __DIR__.'/../Fixtures/golden/barcode/'.barcodeGoldenSlug($case).'.svg';

    if (getenv('QR_GOLDEN_UPDATE') === '1') {
        @mkdir(dirname($fixture), 0777, true);
        file_put_contents($fixture, $svg);
    }

    expect($fixture)->toBeFile()
        ->and($svg)->toBe(file_get_contents($fixture));
})->with(array_keys(barcodeGoldenSvgCases()));

it('keeps the barcode PNG bars identical on GD and Imagick', function (string $typeKey, bool $imagick) {
    if ($imagick && ! extension_loaded('imagick')) {
        $this->markTestSkipped('Imagick is not loaded.');
    }

    [$type, $value] = barcodeGoldenValues()[$typeKey];
    $png = (new BarcodeRenderer(imagick: $imagick))->render(new BarcodeRenderOptions(
        data: $value,
        type: $type,
        format: QrFormat::Png,
        barWidth: 2,
        height: 60,
        margin: $type->getDefaultMargin(),
        foregroundColor: '#000000',
        backgroundColor: '#ffffff',
        showText: false,
    ));
    $fixture = __DIR__.'/../Fixtures/golden/barcode/png.json';
    $all = is_file($fixture) ? (array) json_decode((string) file_get_contents($fixture), true) : [];

    if (getenv('QR_GOLDEN_UPDATE') === '1') {
        $all[$typeKey] = barcodePngFingerprint($png);
        ksort($all);
        file_put_contents($fixture, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    expect(barcodePngFingerprint($png))->toBe($all[$typeKey] ?? null);
})->with(array_keys(barcodeGoldenValues()))->with(['gd' => false, 'imagick' => true]);
