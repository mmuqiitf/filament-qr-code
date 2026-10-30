<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

/**
 * @property Schema $form
 */
class SequenceSmoke extends Page
{
    protected string $view = 'workbench::scan-smoke';

    protected static ?string $slug = 'sequence-smoke';

    protected static ?string $title = 'Sequence smoke';

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
                QrScanSequence::make()
                    ->statePath('receipt')
                    ->steps([
                        ScanStep::make('po')->label('Purchase order')->validateUsing(['regex:/^PO-\d+$/']),
                        ScanStep::make('sku')->label('SKU'),
                    ]),
            ]);
    }
}
