<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeRenderer;
use Mmuqiitf\FilamentQrCode\Rendering\BarcodeRenderOptions;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;

beforeEach(fn () => BarcodeService::flushRenderCache());

/** @return array<string, array{BarcodeType, string, string}> */
function validBarcodes(): array
{
    return [
        'code 128' => [BarcodeType::Code128, 'Hello 123', 'Hello 123'],
        'ean-13' => [BarcodeType::Ean13, '4006381333931', '4006381333931'],
        'ean-8' => [BarcodeType::Ean8, '96385074', '96385074'],
        'upc-a' => [BarcodeType::UpcA, '036000291452', '036000291452'],
        'code 39' => [BarcodeType::Code39, 'HELLO-1', 'HELLO-1'],
        'itf-14' => [BarcodeType::Itf14, '00012345678905', '00012345678905'],
        'gs1-128' => [BarcodeType::Gs1128, '(01)09521101530001(17)251231(10)LOT-1', '(01)09521101530001(17)251231(10)LOT-1'],
    ];
}

it('covers the linear symbologies', function () {
    expect(array_map(fn (BarcodeType $type) => $type->getLabel(), BarcodeType::cases()))
        ->toBe(['Code 128', 'EAN-13', 'EAN-8', 'UPC-A', 'Code 39', 'ITF-14', 'GS1-128']);
});

it('renders every type as an accessible SVG by default', function (BarcodeType $type, string $value, string $text) {
    $service = BarcodeService::make()->type($type)->generate($value);

    expect($service->getFormat())->toBe(QrFormat::Svg)
        ->and($service->toDataUri())->toStartWith('data:image/svg+xml;base64,')
        ->and($service->getRaw())
        ->toContain('<svg')
        ->toContain('role="img"')
        ->toContain('aria-label="'.$type->getLabel().' barcode: '.$text.'"')
        ->toContain('<title>'.$type->getLabel().' barcode: '.$text.'</title>')
        ->toContain('>'.$text.'</text>')
        ->and($service->getText())->toBe($text);
})->with(validBarcodes());

it('hides the human-readable text with showText(false)', function () {
    $withText = BarcodeService::make()->type(BarcodeType::Ean13)->generate('4006381333931');
    $without = BarcodeService::make()->type(BarcodeType::Ean13)->showText(false)->generate('4006381333931');

    expect($without->getRaw())->not->toContain('<text')
        ->and($without->getRaw())->toContain('<title>EAN-13 barcode: 4006381333931</title>')
        ->and(svgHeight($without->getRaw()))->toBeLessThan(svgHeight($withText->getRaw()));
});

function svgHeight(string $svg): int
{
    preg_match('/<svg[^>]* height="(\d+)"/', $svg, $m);

    return (int) $m[1];
}

it('sizes the SVG from the bar width, height and quiet zone', function () {
    $svg = BarcodeService::make()->type(BarcodeType::Ean8)->barWidth(3)->height(80)->margin(5)->showText(false)->generate('96385074')->getRaw();

    // EAN-8 is 67 modules wide; 5-module quiet zone either side.
    expect($svg)->toContain('width="231" height="80"');
});

it('appends the check digit when it is left out', function (BarcodeType $type, string $value, string $text) {
    expect(BarcodeService::make()->type($type)->generate($value)->getText())->toBe($text);
})->with([
    'ean-13' => [BarcodeType::Ean13, '400638133393', '4006381333931'],
    'ean-8' => [BarcodeType::Ean8, '9638507', '96385074'],
    'upc-a' => [BarcodeType::UpcA, '03600029145', '036000291452'],
    'itf-14' => [BarcodeType::Itf14, '0001234567890', '00012345678905'],
]);

it('rejects invalid values with InvalidBarcodeException', function (BarcodeType $type, string $value, string $reason) {
    try {
        BarcodeService::make()->type($type)->generate($value);
    } catch (InvalidBarcodeException $e) {
        expect($e->type)->toBe($type)
            ->and($e->value)->toBe($value)
            ->and($e->getMessage())->toContain($type->getLabel())
            ->and($e->getMessage())->toContain($reason);

        return;
    }

    test()->fail('No InvalidBarcodeException thrown.');
})->with([
    'ean-13 bad check digit' => [BarcodeType::Ean13, '4006381333932', 'check digit'],
    'ean-13 too short' => [BarcodeType::Ean13, '4006381', '12 or 13 digits'],
    'ean-13 letters' => [BarcodeType::Ean13, '40063813339AB', 'digits'],
    'ean-8 bad check digit' => [BarcodeType::Ean8, '96385075', 'check digit'],
    'upc-a bad check digit' => [BarcodeType::UpcA, '036000291453', 'check digit'],
    'upc-a too long' => [BarcodeType::UpcA, '0360002914521', '11 or 12 digits'],
    'itf-14 bad check digit' => [BarcodeType::Itf14, '00012345678906', 'check digit'],
    'itf-14 too short' => [BarcodeType::Itf14, '1234', '13 or 14 digits'],
    'code 39 lowercase' => [BarcodeType::Code39, 'hello', 'Code 39'],
    'code 39 symbol' => [BarcodeType::Code39, 'A~B', 'Code 39'],
    'code 128 non-ascii' => [BarcodeType::Code128, 'café', 'ASCII'],
    'empty' => [BarcodeType::Code128, '', 'empty'],
    'gs1-128 unknown ai' => [BarcodeType::Gs1128, '(05)1234', 'unknown application identifier'],
    'gs1-128 parentheses in a value' => [BarcodeType::Gs1128, '10A(B', 'parentheses'],
    'gs1-128 too long' => [BarcodeType::Gs1128, '(10)'.str_repeat('A', 20).'(21)'.str_repeat('B', 20).'(240)EXTRA', 'too long'],
]);

it('draws the same GS1-128 bars from bracketed data, raw scan data and a symbology prefix', function () {
    $bracketed = '(01)09521101530001(10)LOT-1(17)251231';
    $raw = "010952110153000110LOT-1\x1D17251231";

    $fromBracketed = BarcodeService::make()->type(BarcodeType::Gs1128)->generate($bracketed);
    $fromRaw = BarcodeService::make()->type('gs1-128')->generate($raw);
    $fromPrefix = BarcodeService::make()->type(BarcodeType::Gs1128)->generate(']C1'.$raw);

    expect($fromBracketed->getText())->toBe($bracketed)
        ->and($fromRaw->getRaw())->toBe($fromBracketed->getRaw())
        ->and($fromPrefix->getRaw())->toBe($fromBracketed->getRaw());
});

it('applies foreground and background colours', function () {
    $svg = BarcodeService::make()->color('#1d4ed8')->backgroundColor('#fef3c7')->generate('ABC')->getRaw();

    expect($svg)->toContain('fill="#1d4ed8"')->toContain('fill="#fef3c7"');
});

it('rejects an invalid colour', function () {
    BarcodeService::make()->color('blue')->generate('ABC');
})->throws(InvalidArgumentException::class);

it('escapes the value in the SVG', function () {
    $svg = BarcodeService::make()->generate('<a&"b>')->getRaw();

    expect($svg)->toContain('&lt;a&amp;&quot;b&gt;')->not->toContain('<a&');
});

it('renders PNG with GD and with Imagick', function (bool $imagick) {
    if ($imagick && ! extension_loaded('imagick')) {
        test()->markTestSkipped('Imagick is not loaded.');
    }

    $options = new BarcodeRenderOptions(
        data: '96385074',
        type: BarcodeType::Ean8,
        format: QrFormat::Png,
        barWidth: 2,
        height: 50,
        margin: 10,
        foregroundColor: '#000000',
        backgroundColor: '#ffffff',
        showText: false,
    );
    $png = (new BarcodeRenderer(imagick: $imagick))->render($options);
    $image = imagecreatefromstring($png);

    expect(substr($png, 0, 8))->toBe("\x89PNG\r\n\x1a\n")
        ->and(imagesx($image))->toBe((67 + 20) * 2)
        ->and(imagesy($image))->toBe(50)
        // first bar of the start guard sits right after the quiet zone
        ->and(pixelHex($image, 20, 25))->toBe('000000')
        ->and(pixelHex($image, 19, 25))->toBe('ffffff');
})->with(['gd' => false, 'imagick' => true]);

it('draws the text under a PNG barcode', function () {
    $png = BarcodeService::make()->type(BarcodeType::Ean8)->format('png')->height(50)->generate('96385074');
    $image = imagecreatefromstring($png->getRaw());

    expect($png->toDataUri())->toStartWith('data:image/png;base64,')
        ->and(imagesy($image))->toBeGreaterThan(50);
});

it('caches identical renders', function () {
    $first = BarcodeService::make()->generate('SAME')->getRaw();

    expect(BarcodeService::make()->generate('SAME')->getRaw())->toBe($first)
        ->and(BarcodeService::make()->height(90)->generate('SAME')->getRaw())->not->toBe($first);
});

it('throws when the raw output is read before generate()', function () {
    BarcodeService::make()->getRaw();
})->throws(RuntimeException::class);

it('offers a download response named after the format', function () {
    $response = BarcodeService::make()->fileName('label')->generate('ABC')->download();

    expect($response->headers->get('Content-Disposition'))->toContain('label.svg')
        ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml');
});

function pixelHex(GdImage $image, int $x, int $y): string
{
    $rgb = imagecolorsforindex($image, imagecolorat($image, $x, $y));

    return sprintf('%02x%02x%02x', $rgb['red'], $rgb['green'], $rgb['blue']);
}
