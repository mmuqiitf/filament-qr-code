<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class QrCodeDoctorCommand extends Command
{
    protected $signature = 'qr-code:doctor';

    protected $description = 'Check the Filament QR Code install: image backends, fonts, assets, config, and routes.';

    public function handle(): int
    {
        $failures = 0;

        $failures += $this->checkImageBackend();
        $failures += $this->checkSystemFonts();
        $failures += $this->checkDistFreshness();
        $failures += $this->checkSignedRoute();
        $failures += $this->checkConfig();
        $failures += $this->checkSecureContext();
        $failures += $this->checkCacheStore();

        if ($failures > 0) {
            $this->error("qr-code:doctor found {$failures} problem(s). See the troubleshooting table in README.md.");

            return self::FAILURE;
        }

        $this->info('qr-code:doctor passed: QR generation and scanning are ready.');

        return self::SUCCESS;
    }

    private function checkImageBackend(): int
    {
        if (extension_loaded('imagick') || extension_loaded('gd')) {
            $backend = extension_loaded('imagick') ? 'imagick' : 'gd';
            $this->line("  [PASS] PNG backend available ({$backend}).");

            return 0;
        }

        $this->error('  [FAIL] Neither imagick nor gd is installed: PNG generation needs one of them.');

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
            $hasRoute = app('router')->has('filament-qr-code.image');
        } catch (Throwable) {
            $hasRoute = false;
        }

        if (! $hasRoute) {
            $this->error('  [FAIL] Route filament-qr-code.image is not registered: lazy table previews will fall back to inline data-URIs.');

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
}
