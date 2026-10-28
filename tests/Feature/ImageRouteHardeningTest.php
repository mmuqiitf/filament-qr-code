<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

beforeEach(function () {
    QrCodeService::flushRenderCache();
    QrColumn::flushDataUriCache();
});

function hardenedLogo(string $dir = 'inside'): string
{
    return (string) realpath(__DIR__.'/../Fixtures/logos/'.$dir.'/logo.png');
}

it('keeps 1.0.0 behaviour when no hardening is configured', function () {
    $url = QrColumn::make('qr')->data('DEFAULTS')->getModalUrl();

    expect($url)->not->toContain('expires=');

    $this->get($url)->assertOk()->assertHeader('Cache-Control', 'immutable, max-age=86400, public');

    foreach (range(1, 70) as $_) {
        $this->get($url)->assertOk();
    }

    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'X', 'foreground' => '#fff']))->assertOk();
    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'X', 'logo' => hardenedLogo('outside')]))->assertOk();
});

it('signs expiring URLs and caps max-age when a ttl is set', function () {
    config()->set('qr-code.image_route.ttl', 10);

    $url = QrColumn::make('qr')->data('TTL')->getModalUrl();

    expect($url)->toContain('expires=');
    $this->get($url)->assertOk()->assertHeader('Cache-Control', 'immutable, max-age=600, public');

    $this->travel(11)->minutes();
    $this->get($url)->assertForbidden();
});

it('never raises max-age above a day for long ttls', function () {
    config()->set('qr-code.image_route.ttl', 60 * 24 * 7);

    $this->get(QrColumn::make('qr')->data('TTL')->getModalUrl())
        ->assertHeader('Cache-Control', 'immutable, max-age=86400, public');
});

it('throttles the image route when configured', function () {
    config()->set('qr-code.image_route.throttle', '60,1');
    $url = URL::signedRoute('filamentQrCode.image', ['data' => 'THROTTLE']);

    foreach (range(1, 60) as $_) {
        $this->get($url)->assertOk();
    }

    $this->get($url)->assertStatus(429);
});

it('rejects non #rrggbb colours when strict colours are on', function (array $params) {
    config()->set('qr-code.image_route.strict_colors', true);

    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'C', ...$params]))->assertStatus(422);
})->with([
    'named' => [['foreground' => 'red']],
    'short hex' => [['background' => '#fff']],
    'no hash' => [['foreground' => '000000']],
    'bad digits' => [['foreground' => '#zzzzzz']],
]);

it('accepts #rrggbb colours when strict colours are on', function () {
    config()->set('qr-code.image_route.strict_colors', true);

    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'C', 'foreground' => '#0F172A', 'background' => '#ffffff']))
        ->assertOk();
});

it('returns 422 for a logo outside the base paths on the image route', function () {
    config()->set('qr-code.generator.logo_base_paths', [dirname(hardenedLogo())]);

    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'L', 'logo' => hardenedLogo('outside')]))->assertStatus(422);
    $this->get(URL::signedRoute('filamentQrCode.image', ['data' => 'L', 'logo' => hardenedLogo()]))
        ->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('skips a disallowed logo in inline renders and logs a warning', function () {
    config()->set('qr-code.generator.logo_base_paths', [dirname(hardenedLogo())]);
    Log::spy();

    $guarded = QrCodeService::make()->logo(hardenedLogo('outside'))->generate('INLINE')->getRaw();
    QrCodeService::flushRenderCache();
    $plain = QrCodeService::make()->format('png')->errorCorrection('H')->generate('INLINE')->getRaw();

    $pixels = function (string $png): string {
        ob_start();
        imagepng(imagecreatefromstring($png));

        return md5((string) ob_get_clean());
    };

    expect($pixels($guarded))->toBe($pixels($plain));
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message) => str_contains($message, 'logo_base_paths'))->once();
});

it('reports hardening state in the doctor', function () {
    $this->artisan('qr-code:doctor')
        ->expectsOutputToContain('[INFO] qr-code.generator.logo_base_paths is not set');

    config()->set('qr-code.generator.logo_base_paths', ['/definitely/missing/dir']);

    $this->artisan('qr-code:doctor')
        ->expectsOutputToContain('[WARN] Logo base path does not exist: /definitely/missing/dir');
});
