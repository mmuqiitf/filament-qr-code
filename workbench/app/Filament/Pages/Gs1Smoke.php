<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/**
 * A GS1 scanner in a submit form: Enter on a typed label sends it first and
 * submits only when the label was accepted.
 *
 * @property Schema $form
 */
class Gs1Smoke extends Page
{
    protected string $view = 'workbench::gs1-smoke';

    protected static ?string $slug = 'gs1-smoke';

    protected static ?string $title = 'GS1 smoke';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public int $submitted = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                QrScanner::make('label')->label('GS1 label')->gs1(fill: ['01' => 'gtin'], require: ['01', '17'])->required(),
                TextInput::make('gtin')->label('GTIN')->readOnly()->required(),
            ]);
    }

    public function submit(): void
    {
        $this->form->getState();
        $this->submitted++;
        $this->form->fill();
    }
}
