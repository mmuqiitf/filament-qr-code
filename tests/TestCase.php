<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Support\ViewErrorBag;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Mmuqiitf\FilamentQrCode\FilamentQrCodeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        view()->share('errors', new ViewErrorBag);

        // Booting a Livewire component sets this static flag, and Livewire's middleware
        // then sends the next response as no-cache. Reset it so a component test that
        // ran earlier (random order) cannot change another test's Cache-Control.
        SupportDisablingBackButtonCache::$disableBackButtonCache = false;
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentQrCodeServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        // Pin the cache store: `testbench workbench:build` (browser tests) writes a
        // skeleton .env with CACHE_STORE=database, which has no table under Pest.
        config()->set('cache.default', 'array');
        config()->set('app.key', 'base64:6Cu/ozkhw+hgSU47S7oDg2BgSbFixMIWoFsRf5DQOkI=');
        $app->singleton(DataStore::class);
    }
}
