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
use Mmuqiitf\FilamentQrCode\Listeners\LogQrCodeScanListener;
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
            ->hasCommand(Commands\QrCodeDoctorCommand::class)
            ->hasCommand(Commands\MakeQrScanFieldCommand::class);
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
        $dist = __DIR__.'/../resources/dist';

        return [
            Js::make('filament-qr-code-scripts', $dist.'/filament-qr-code.js')->module(),
            // Sibling of the main script: Vite 8/Rolldown lazy chunks import
            // helpers from the entry by its build name, which Filament renames
            // on publish. A self-contained decoder next to the scripts file
            // keeps camera pages lazy and `filament:assets`-friendly.
            Js::make('filament-qr-code-decoder', $dist.'/filament-qr-code-decoder.js')
                ->module()
                ->loadedOnRequest(),
            Css::make('filament-qr-code-styles', $dist.'/filament-qr-code.css'),
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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/qr-scan-field.stub' => base_path('stubs/filament-qr-code/qr-scan-field.stub'),
                __DIR__.'/../stubs/qr-scan-sequence-field.stub' => base_path('stubs/filament-qr-code/qr-scan-sequence-field.stub'),
            ], 'filament-qr-code-stubs');
        }

        static::registerAssetsOnce();

        Event::listen(QrCodeScanned::class, LogQrCodeScanListener::class);

        Route::middleware('web')->group(function (): void {
            Route::get('filament-qr-code/qr-image', QrImageController::class)
                ->name('filamentQrCode.image')
                ->middleware('signed');
        });
    }
}
