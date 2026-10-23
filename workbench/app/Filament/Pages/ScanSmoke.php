<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/**
 * @property Schema $form
 */
class ScanSmoke extends Page
{
    protected string $view = 'workbench::scan-smoke';

    protected static ?string $slug = 'scan-smoke';

    protected static ?string $title = 'Scan smoke';

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
                QrScanner::make('wedge')->label('Wedge')->validateUsing(['string', 'starts_with:WEDGE-']),
                QrScanner::make('camera')->label('Camera')->formats([BarcodeFormat::QrCode]),
            ]);
    }
}
