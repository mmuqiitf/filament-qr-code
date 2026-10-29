<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

beforeEach(fn () => QrCodeService::flushRenderCache());

it('serves the locked SVG bytes and headers from the signed image route', function () {
    $url = URL::signedRoute('filamentQrCode.image', [
        'data' => 'GOLDEN-1.0.0', 'size' => 240, 'margin' => 1,
        'format' => 'svg', 'foreground' => '#0f172a', 'background' => '#f8fafc', 'ec' => 'Q',
    ]);

    $response = $this->get($url);
    $fixture = __DIR__.'/../Fixtures/golden/http/image-route-svg.svg';

    if (getenv('QR_GOLDEN_UPDATE') === '1') {
        @mkdir(dirname($fixture), 0777, true);
        file_put_contents($fixture, $response->getContent());
    }

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('Cache-Control', 'immutable, max-age=86400, public');

    expect($response->getContent())->toBe(file_get_contents($fixture));
});

it('rejects an unsigned or tampered image URL', function () {
    $url = URL::signedRoute('filamentQrCode.image', ['data' => 'GOLDEN-1.0.0']);

    $this->get(str_replace('GOLDEN', 'TAMPER', $url))->assertForbidden();
    $this->get(route('filamentQrCode.image', ['data' => 'GOLDEN-1.0.0']))->assertForbidden();
});
