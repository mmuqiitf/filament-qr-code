<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Mmuqiitf\FilamentQrCode\Console\Commands;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Http\Controllers\QrImageController;
use Mmuqiitf\FilamentQrCode\Listeners\LogQrCodeScan;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentQrCodeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-qr-code';

    protected static bool $assetsRegistered = false;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('qr-code')
            ->hasTranslations()
            ->hasViews(static::$name)
            ->hasCommand(Commands\QrCodeDoctorCommand::class);
    }

    public static function registerAssetsOnce(): void
    {
        if (static::$assetsRegistered) {
            return;
        }

        static::$assetsRegistered = true;

        FilamentAsset::register(static::assets(), package: 'mmuqiitf/filament-qr-code');
    }

    /**
     * @return array<int, Css|Js>
     */
    public static function assets(): array
    {
        return [
            Js::make('filament-qr-code-scripts', __DIR__.'/../resources/dist/filament-qr-code.js')->module(),
            Css::make('filament-qr-code-styles', __DIR__.'/../resources/dist/filament-qr-code.css'),
        ];
    }

    public static function resetAssetsRegistration(): void
    {
        static::$assetsRegistered = false;
    }

    public function packageRegistered(): void
    {
        // The once-guard is process-static, but providers reboot for every
        // app instance (feature suites, Octane): reset it here so each boot
        // registers assets onto its own fresh AssetManager instead of
        // silently serving later boots without the package bundle.
        static::$assetsRegistered = false;

        $this->app->bind(QrCodeService::class, function () {
            return new QrCodeService;
        });
    }

    public function packageBooted(): void
    {
        static::registerAssetsOnce();

        Event::listen(QrCodeScanned::class, LogQrCodeScan::class);

        Route::middleware('web')->group(function (): void {
            Route::get('filament-qr-code/qr-image', QrImageController::class)
                ->name('filament-qr-code.image')
                ->middleware('signed');
        });
    }
}
