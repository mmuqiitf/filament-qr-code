<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/**
 * @property Schema $form
 */
class OfflineSmoke extends Page
{
    protected string $view = 'workbench::offline-smoke';

    protected static ?string $slug = 'offline-smoke';

    protected static ?string $title = 'Offline smoke';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrScanner::make('banner')->label('Banner')->offline('banner')->sound(false)->vibrate(false),
                QrScanner::make('blocked')->label('Blocked')->blockWhenOffline()->sound(false)->vibrate(false),
            ]);
    }
}
