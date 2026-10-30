<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\View;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mmuqiitf\FilamentQrCode\FilamentQrCodePlugin;
use Workbench\App\Filament\Pages\CollectorSmoke;
use Workbench\App\Filament\Pages\CollectSmoke;
use Workbench\App\Filament\Pages\ModalSmoke;
use Workbench\App\Filament\Pages\ScanSmoke;
use Workbench\App\Filament\Pages\SequenceSmoke;
use Workbench\App\Filament\Pages\TableSmoke;

/**
 * Guest-only panel for the Playwright smoke tests (`npm run test:browser`).
 * Never registered by the package itself.
 */
class WorkbenchPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        View::addNamespace('workbench', __DIR__.'/../../resources/views');
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('workbench')
            ->path('workbench')
            ->topbar(false)
            ->plugin(FilamentQrCodePlugin::make())
            ->pages([ScanSmoke::class, CollectSmoke::class, CollectorSmoke::class, ModalSmoke::class, SequenceSmoke::class, TableSmoke::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
