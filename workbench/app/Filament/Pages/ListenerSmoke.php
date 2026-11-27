<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

/**
 * @property Schema $form
 */
class ListenerSmoke extends Page
{
    protected string $view = 'workbench::listener-smoke';

    protected static ?string $slug = 'listener-smoke';

    protected static ?string $title = 'Listener smoke';

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
                TextInput::make('sku')->label('SKU'),
                QrHardwareScannerListener::make(['sku'])->sound(false)->vibrate(false),
            ]);
    }
}
