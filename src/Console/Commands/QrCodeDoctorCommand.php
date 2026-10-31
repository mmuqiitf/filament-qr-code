<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Console\Commands;

use Filament\Support\Facades\FilamentAsset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Mmuqiitf\FilamentQrCode\Support\LogoPathGuard;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class QrCodeDoctorCommand extends Command
{
    protected $signature = 'qr-code:doctor';

    protected $description = 'Check the Filament QR & Barcode install: image backends, fonts, assets, config, and routes.';

    public function handle(): int
    {
        $failures = 0;

        $failures += $this->checkImageBackend();
        $failures += $this->checkSystemFonts();
        $failures += $this->checkDistFreshness();
        $failures += $this->checkPublishedAssets();
        $failures += $this->checkSignedRoute();
        $failures += $this->checkConfig();
        $failures += $this->checkSecureContext();
        $failures += $this->checkCacheStore();
        $failures += $this->checkImageRouteHardening();
        $failures += $this->checkImageRouteConfig();

        if ($failures > 0) {
            $this->error("qr-code:doctor found {$failures} problem(s). See the troubleshooting table in README.md.");

            return self::FAILURE;
        }

        $this->info('qr-code:doctor passed: QR generation and scanning are ready.');

        return self::SUCCESS;
    }

    private function checkImageBackend(): int
    {
        $loaded = sprintf(
            'imagick: %s, gd: %s',
            extension_loaded('imagick') ? 'yes' : 'no',
            extension_loaded('gd') ? 'yes' : 'no',
        );

        if (extension_loaded('imagick') || extension_loaded('gd')) {
            $backend = extension_loaded('imagick') ? 'imagick' : 'gd';
            $this->line("  [PASS] PNG backend available ({$backend}; {$loaded}).");

            return 0;
        }

        $this->error("  [FAIL] Neither imagick nor gd is installed ({$loaded}): PNG generation needs one of them.");

        return 1;
    }

    private function checkSystemFonts(): int
    {
        $candidates = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $this->line('  [PASS] System font found for text overlays.');

                return 0;
            }
        }

        $this->warn('  [WARN] No DejaVu system font found: text overlays fall back to GD bitmap fonts (install fonts-dejavu-core).');

        return 0;
    }

    private function checkDistFreshness(): int
    {
        $packageRoot = dirname(__DIR__, 3);
        $distJs = $packageRoot.'/resources/dist/filament-qr-code.js';

        if (! file_exists($distJs)) {
            $this->error('  [FAIL] resources/dist/filament-qr-code.js is missing: run npm run build.');

            return 1;
        }

        $newestSource = 0;
        foreach (['resources/js', 'resources/css'] as $dir) {
            $full = $packageRoot.'/'.$dir;
            if (! is_dir($full)) {
                continue;
            }

            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($full)) as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                // Vitest co-locates with sources but never ships in the bundle.
                if (str_ends_with($file->getFilename(), '.test.js')) {
                    continue;
                }

                $newestSource = max($newestSource, $file->getMTime());
            }
        }

        if ($newestSource > filemtime($distJs)) {
            $this->error('  [FAIL] resources/js or resources/css is newer than resources/dist: run npm run build and php artisan filament:assets.');

            return 1;
        }

        $this->line('  [PASS] Compiled assets are newer than sources.');

        return 0;
    }

    private function checkSignedRoute(): int
    {
        try {
            $hasRoute = app('router')->has('filamentQrCode.image');
        } catch (Throwable) {
            $hasRoute = false;
        }

        if (! $hasRoute) {
            $this->error('  [FAIL] Route filamentQrCode.image is not registered: lazy table previews will fall back to inline data-URIs.');

            return 1;
        }

        if (empty($this->stringConfig('app.key'))) {
            $this->error('  [FAIL] APP_KEY is empty: signed preview URLs cannot be generated.');

            return 1;
        }

        $this->line('  [PASS] Signed preview route registered with an app key.');

        return 0;
    }

    private function checkConfig(): int
    {
        $problems = 0;

        $burst = $this->intConfig('qr-code.hardware_scanner.burst_threshold_ms', 50);
        if ($burst <= 0) {
            $this->warn('  [WARN] qr-code.hardware_scanner.burst_threshold_ms should be positive.');
            $problems++;
        }

        $fps = $this->intConfig('qr-code.camera.fps', 25);
        if ($fps < 1 || $fps > 120) {
            $this->warn('  [WARN] qr-code.camera.fps should be between 1 and 120.');
            $problems++;
        }

        $beep = $this->intConfig('qr-code.feedback.beep_frequency', 880);
        if ($beep < 100) {
            $this->warn('  [WARN] qr-code.feedback.beep_frequency below 100Hz is inaudible on most devices.');
            $problems++;
        }

        if ($problems === 0) {
            $this->line('  [PASS] Config values are sane.');
        }

        return 0;
    }

    private function checkSecureContext(): int
    {
        $url = $this->stringConfig('app.url');

        if (str_starts_with($url, 'https://') || str_contains($url, 'localhost') || str_contains($url, '127.0.0.1')) {
            $this->line('  [PASS] APP_URL looks camera-capable (https or localhost).');

            return 0;
        }

        $this->warn('  [WARN] APP_URL is not https/localhost: browsers block camera access outside secure contexts.');

        return 0;
    }

    private function checkCacheStore(): int
    {
        try {
            Cache::store($this->cacheStoreName())->get('filament-qr-code:doctor-probe');
            $this->line('  [PASS] Persistent QR cache store is reachable.');

            return 0;
        } catch (Throwable) {
            $this->warn('  [WARN] Persistent QR cache store is unreachable: renders fall back to per-request caching.');

            return 0;
        }
    }

    private function stringConfig(string $key): string
    {
        $value = config($key);

        return is_string($value) ? $value : '';
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    private function cacheStoreName(): ?string
    {
        $value = config('qr-code.generator.cache_store');

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function checkImageRouteHardening(): int
    {
        $basePaths = LogoPathGuard::basePaths();

        if ($basePaths === null) {
            $this->line('  [INFO] qr-code.generator.logo_base_paths is not set: any readable image path can be embedded as a logo. Set it if logo paths can come from user input.');
        } else {
            $missing = array_filter($basePaths, fn (string $path) => ! is_dir($path));

            foreach ($missing as $path) {
                $this->warn("  [WARN] Logo base path does not exist: {$path}");
            }

            if ($basePaths === []) {
                $this->warn('  [WARN] qr-code.generator.logo_base_paths is empty: every logo will be rejected.');
            } elseif ($missing === []) {
                $this->line('  [PASS] Logos restricted to qr-code.generator.logo_base_paths.');
            }
        }

        if (config('qr-code.image_route.ttl') === null && config('qr-code.image_route.throttle') === null) {
            $this->line('  [INFO] Image route has no ttl or throttle (opt-in hardening: qr-code.image_route).');
        }

        return 0;
    }

    private function checkPublishedAssets(): int
    {
        $missing = [];
        $stale = [];

        $package = ['mmuqiitf/filament-qr-code'];

        foreach ([...FilamentAsset::getScripts($package, withCore: false), ...FilamentAsset::getStyles($package)] as $asset) {
            $source = $asset->getPath();

            if ($asset->isRemote() || $source === null || ! is_file($source)) {
                continue;
            }

            $published = $asset->getPublicPath();

            if (! is_file($published)) {
                $missing[] = $asset->getRelativePublicPath();
            } elseif (md5_file($published) !== md5_file($source)) {
                $stale[] = $asset->getRelativePublicPath();
            }
        }

        if ($missing !== []) {
            $this->error('  [FAIL] Filament assets are not published ('.implode(', ', $missing).'): run php artisan filament:assets.');

            return 1;
        }

        if ($stale !== []) {
            $this->warn('  [WARN] Published assets are out of date ('.implode(', ', $stale).'): run php artisan filament:assets after upgrading.');

            return 0;
        }

        $this->line('  [PASS] Published Filament assets match the package build.');

        return 0;
    }

    private function checkImageRouteConfig(): int
    {
        $problems = 0;

        $ttl = config('qr-code.image_route.ttl');
        if ($ttl !== null && (! is_numeric($ttl) || (float) $ttl <= 0)) {
            $this->error('  [FAIL] qr-code.image_route.ttl must be a positive number of minutes, or null.');
            $problems++;
        }

        $throttle = config('qr-code.image_route.throttle');
        if ($throttle !== null && (! is_string($throttle) || preg_match('/^\s*[1-9]\d*\s*(,\s*\d+(\.\d+)?\s*)?$/', $throttle) !== 1)) {
            $this->error("  [FAIL] qr-code.image_route.throttle must look like '60,1' (requests,minutes), or null.");
            $problems++;
        }

        $strictColors = config('qr-code.image_route.strict_colors', false);
        if (! is_bool($strictColors)) {
            $this->error('  [FAIL] qr-code.image_route.strict_colors must be true or false.');
            $problems++;
        }

        if ($problems > 0) {
            return $problems;
        }

        $this->line(sprintf(
            '  [PASS] Image route config: %s, %s, strict colours %s.',
            $ttl === null ? 'URLs never expire' : "URLs expire after {$ttl} minutes",
            $throttle === null ? 'no throttle' : "throttle {$throttle}",
            $strictColors ? 'on' : 'off',
        ));

        return 0;
    }
}
