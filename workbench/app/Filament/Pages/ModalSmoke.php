<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class ModalSmoke extends Page
{
    protected string $view = 'workbench::collect-smoke';

    protected static ?string $slug = 'modal-smoke';

    protected static ?string $title = 'Modal smoke';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanInModal')
                ->label('Scan in modal')
                ->schema([QrScanner::make('code')->label('Code')])
                ->action(fn (): null => null),
            Action::make('scanInSlideOver')
                ->label('Scan in slide-over')
                ->slideOver()
                ->schema([QrScanner::make('code')->label('Code')])
                ->action(fn (): null => null),
        ];
    }
}
