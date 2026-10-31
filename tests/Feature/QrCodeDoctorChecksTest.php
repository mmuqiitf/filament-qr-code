<?php

declare(strict_types=1);

use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\File;

/** @return list<Asset> */
function doctorPackageAssets(): array
{
    $package = ['mmuqiitf/filament-qr-code'];

    return [...FilamentAsset::getScripts($package, withCore: false), ...FilamentAsset::getStyles($package)];
}

function publishDoctorAssets(): void
{
    foreach (doctorPackageAssets() as $asset) {
        File::ensureDirectoryExists(dirname($asset->getPublicPath()));
        File::copy((string) $asset->getPath(), $asset->getPublicPath());
    }
}

beforeEach(function () {
    $this->originalPublicPath = public_path();
    $this->publicPath = sys_get_temp_dir().'/qr-doctor-public-'.uniqid();
    File::ensureDirectoryExists($this->publicPath);
    app()->usePublicPath($this->publicPath);

    // git checkout can write sources after the bundle; keep the
    // unrelated dist freshness check out of these assertions.
    $this->distJs = __DIR__.'/../../resources/dist/filament-qr-code.js';
    $this->distMtime = (int) filemtime($this->distJs);
    touch($this->distJs, time() + 60);
});

afterEach(function () {
    touch($this->distJs, $this->distMtime);
    app()->usePublicPath($this->originalPublicPath);
    File::deleteDirectory($this->publicPath);
});

it('reports install health through the doctor command', function () {
    publishDoctorAssets();

    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain('PNG backend available')
        ->expectsOutputToContain('Compiled assets are newer than sources')
        ->expectsOutputToContain('Signed preview route registered')
        ->expectsOutputToContain('qr-code:doctor passed');
});

it('reports which PNG backends are loaded', function () {
    publishDoctorAssets();

    $imagick = extension_loaded('imagick') ? 'yes' : 'no';
    $gd = extension_loaded('gd') ? 'yes' : 'no';

    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain("imagick: {$imagick}, gd: {$gd}");
});

it('fails when the Filament assets are not published', function () {
    $this->artisan('qr-code:doctor')
        ->assertFailed()
        ->expectsOutputToContain('[FAIL] Filament assets are not published (js/mmuqiitf/filament-qr-code/filament-qr-code-scripts.js, js/mmuqiitf/filament-qr-code/filament-qr-code-decoder.js, css/mmuqiitf/filament-qr-code/filament-qr-code-styles.css): run php artisan filament:assets.');
});

it('warns when the published assets are out of date', function () {
    publishDoctorAssets();
    file_put_contents(doctorPackageAssets()[0]->getPublicPath(), '// stale');

    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain('[WARN] Published assets are out of date (js/mmuqiitf/filament-qr-code/filament-qr-code-scripts.js): run php artisan filament:assets after upgrading.');
});

it('passes when the published assets match the package build', function () {
    publishDoctorAssets();

    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain('[PASS] Published Filament assets match the package build.');
});

it('summarises a valid image route config', function () {
    publishDoctorAssets();
    config()->set('qr-code.image_route', ['ttl' => 10, 'throttle' => '60,1', 'strict_colors' => true]);

    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain('[PASS] Image route config: URLs expire after 10 minutes, throttle 60,1, strict colours on.');
});

it('fails on an invalid image route config', function (string $key, mixed $value, string $message) {
    publishDoctorAssets();
    config()->set("qr-code.image_route.{$key}", $value);

    $this->artisan('qr-code:doctor')
        ->assertFailed()
        ->expectsOutputToContain("[FAIL] qr-code.image_route.{$key} {$message}");
})->with([
    'zero ttl' => ['ttl', 0, 'must be a positive number of minutes, or null'],
    'negative ttl' => ['ttl', -5, 'must be a positive number of minutes, or null'],
    'text ttl' => ['ttl', 'soon', 'must be a positive number of minutes, or null'],
    'text throttle' => ['throttle', 'sixty', "must look like '60,1' (requests,minutes), or null"],
    'zero throttle' => ['throttle', '0,1', "must look like '60,1' (requests,minutes), or null"],
    'non-bool strict colours' => ['strict_colors', 'yes', 'must be true or false'],
]);

it('passes when every logo base path exists', function () {
    publishDoctorAssets();
    config()->set('qr-code.generator.logo_base_paths', [$this->publicPath]);

    $this->artisan('qr-code:doctor')
        ->assertSuccessful()
        ->expectsOutputToContain('[PASS] Logos restricted to qr-code.generator.logo_base_paths.');
});
