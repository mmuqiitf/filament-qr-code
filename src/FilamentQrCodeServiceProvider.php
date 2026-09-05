<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentQrCodeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-qr-code';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('qr-code')
            ->hasViews(static::$name);
    }

    public function packageRegistered(): void
    {
        $this->app->bind(QrCodeService::class, function () {
            return new QrCodeService;
        });
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register([
            Js::make('filament-qr-code-scripts', __DIR__.'/../resources/dist/filament-qr-code.js'),
            Css::make('filament-qr-code-styles', __DIR__.'/../resources/dist/filament-qr-code.css'),
        ], package: 'mmuqiitf/filament-qr-code');
    }
}
