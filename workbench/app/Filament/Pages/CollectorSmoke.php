<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * @property Schema $form
 */
class CollectorSmoke extends Page
{
    protected string $view = 'workbench::collector-smoke';

    protected static ?string $slug = 'collector-smoke';

    protected static ?string $title = 'Collector smoke';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<string> */
    public array $handled = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrCollector::make('codes')->label('Codes')->allowDuplicates(false),
            ]);
    }

    public function handleCollectorScan(string $code): void
    {
        $this->handled[] = $code;
    }
}
