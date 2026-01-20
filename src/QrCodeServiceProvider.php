<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Foundation\Console\AboutCommand;
use Mmuqiitf\FilamentQrCode\Commands\InstallCommand;
use Mmuqiitf\FilamentQrCode\Testing\TestsQrCodeScanner;
use Spatie\LaravelPackageTools\Commands\InstallCommand as SpatieInstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class QrCodeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-qr-code';

    public static string $viewNamespace = 'filament-qr-code';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name(static::$name)
            ->hasConfigFile('qr-code')
            ->hasViews(static::$viewNamespace)
            ->hasTranslations()
            ->hasCommands([
                InstallCommand::class,
            ])
            ->hasInstallCommand(function (SpatieInstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('mmuqiitf/filament-qr-code');
            });
    }

    public function packageRegistered(): void
    {
        //
    }

    public function packageBooted(): void
    {
        // Register assets with Filament's Asset Manager
        // Assets are registered in packageBooted() to ensure Filament is ready
        FilamentAsset::register(
            assets: [
                AlpineComponent::make(
                    'qr-scanner',
                    __DIR__.'/../resources/dist/js/components/qr-scanner.js'
                ),
                Css::make(
                    'qr-code-styles',
                    __DIR__.'/../resources/dist/css/qr-code.css'
                )->loadedOnRequest(),
            ],
            package: 'mmuqiitf/filament-qr-code'
        );

        // Add package info to artisan about command
        AboutCommand::add('Filament QR Code', fn () => [
            'Version' => '1.0.0',
        ]);

        // Register testing helpers (for testing the package itself)
        if (app()->runningUnitTests()) {
            $this->app->bind(TestsQrCodeScanner::class, fn () => new TestsQrCodeScanner);
        }
    }
}
