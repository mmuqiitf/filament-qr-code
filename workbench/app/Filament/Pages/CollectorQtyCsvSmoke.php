<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

/**
 * @property Schema $form
 */
class CollectorQtyCsvSmoke extends Page
{
    protected string $view = 'workbench::collector-qty-csv-smoke';

    protected static ?string $slug = 'collector-qty-csv-smoke';

    protected static ?string $title = 'Collector quantity & CSV smoke';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'codes' => [
                ['code' => 'PRD-100', 'qty' => 2],
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrCollector::make('codes')
                    ->label('Audit items')
                    ->quantity()
                    ->maxItems(3)
                    ->csv(fileName: 'smoke-audit.csv')
                    ->delayBetweenScans(0)
                    ->normalizeWith(uppercase: true)
                    ->itemRules(['regex:/^PRD-\d+$/']),
            ]);
    }
}
