<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * @property Schema $form
 */
class RapidCollectorSmoke extends Page
{
    protected string $view = 'workbench::collector-smoke';

    protected static ?string $slug = 'rapid-collector-smoke';

    protected static ?string $title = 'Rapid collector smoke';

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
                QrCollector::make('rapid')->label('Rapid')->delayBetweenScans(0),
            ]);
    }

    /** @var list<string> */
    public array $handled = [];

    public function handleCollectorScan(string $code): void
    {
        $this->handled[] = $code;
    }
}
