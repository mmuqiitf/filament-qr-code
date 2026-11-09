<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;
use Mmuqiitf\FilamentQrCode\Tables\Columns\BarcodeColumn;

beforeEach(function (): void {
    BarcodeService::flushRenderCache();
});

it('serves the same bytes the column would have drawn inline', function () {
    $column = BarcodeColumn::make('code')->data('SKU-TABLE-1');
    $render = $column->getBarcodeRender();

    expect($render['uri'])->toContain('barWidth=1')
        ->and($render['uri'])->toContain('height=32')
        ->and($render['uri'])->toContain('showText=0')
        ->and($render['uri'])->toContain('margin=10')
        ->and($render['uri'])->not->toContain('expires=')
        ->and($render['alt'])->toBe('Code 128 barcode: SKU-TABLE-1');

    $response = $this->get($render['uri']);

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'immutable, max-age=86400, private');

    expect($response->headers->getCookies())->toBe([])
        ->and($response->getContent())->toBe($column->getBarcodeService()->getRaw())
        ->and($response->getContent())->not->toContain('<text')
        ->and($response->getContent())->toContain('height="32"');
});

it('signs a group separator and draws the same GS1-128 bars', function () {
    $raw = "010952110153000110LOT-1\x1D17251231";
    $column = BarcodeColumn::make('gtin')->type(BarcodeType::Gs1128)->data($raw);

    $response = $this->get($column->getBarcodeRender()['uri'])->assertOk();

    expect($response->getContent())->toBe($column->getBarcodeService()->getRaw())
        ->and($column->getBarcodeRender()['alt'])->toBe('GS1-128 barcode: (01)09521101530001(10)LOT-1(17)251231');
});

it('resolves an ITF-14 quiet zone of zero into the signed url', function () {
    $uri = BarcodeColumn::make('case')->type(BarcodeType::Itf14)->data('00012345678905')->getBarcodeRender()['uri'];

    expect($uri)->toContain('margin=0')
        ->and($uri)->toContain('type=itf14');
});

it('expires column urls and caps max-age when a ttl is set', function () {
    config()->set('qr-code.image_route.ttl', 10);

    $uri = BarcodeColumn::make('code')->data('TTL')->getBarcodeRender()['uri'];

    expect($uri)->toContain('expires=');
    $this->get($uri)->assertOk()->assertHeader('Cache-Control', 'immutable, max-age=600, private');

    $this->travel(11)->minutes();
    $this->get($uri)->assertForbidden();
});

it('rejects an unsigned barcode url', function () {
    $this->get(route('filamentQrCode.barcode', ['data' => 'X']))->assertForbidden();
});

it('returns 422 for a value the symbology rejects', function () {
    $this->get(URL::signedRoute('filamentQrCode.barcode', [
        'data' => '4006381333932',
        'type' => 'ean13',
    ]))->assertStatus(422);
});

it('returns 422 for invalid query input', function (array $params) {
    $this->get(URL::signedRoute('filamentQrCode.barcode', $params))->assertStatus(422);
})->with([
    'missing data' => [['type' => 'code128']],
    'unknown type' => [['data' => 'X', 'type' => 'qr']],
    'bar width' => [['data' => 'X', 'barWidth' => 0]],
    'height' => [['data' => 'X', 'height' => 9]],
    'format' => [['data' => 'X', 'format' => 'gif']],
    'colour' => [['data' => 'X', 'foreground' => 'red']],
]);

it('rejects short hex on the barcode route when strict colours are on', function () {
    config()->set('qr-code.image_route.strict_colors', true);

    $this->get(URL::signedRoute('filamentQrCode.barcode', ['data' => 'X', 'foreground' => '#fff']))->assertStatus(422);
    $this->get(URL::signedRoute('filamentQrCode.barcode', ['data' => 'X', 'foreground' => '#112233']))->assertOk();
});

it('draws text when the barcode route omits showText', function () {
    $response = $this->get(URL::signedRoute('filamentQrCode.barcode', ['data' => 'X']));

    $response->assertOk();
    expect($response->getContent())->toContain('<text');
});

it('throttles the barcode route with the shared image route limit', function () {
    config()->set('qr-code.image_route.throttle', '2,1');
    $uri = URL::signedRoute('filamentQrCode.barcode', ['data' => 'THROTTLE']);

    $this->get($uri)->assertOk();
    $this->get($uri)->assertOk();
    $this->get($uri)->assertStatus(429);
});
