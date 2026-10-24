<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

class CollectSmoke extends Page
{
    protected string $view = 'workbench::collect-smoke';

    protected static ?string $slug = 'collect-smoke';

    protected static ?string $title = 'Collect smoke';

    protected function getHeaderActions(): array
    {
        return [QrCollectAction::make()];
    }
}
