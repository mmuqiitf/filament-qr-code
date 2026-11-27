<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;

class ProductEdit extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'workbench::product-edit';

    protected static ?string $slug = 'product-edit';

    protected static ?string $title = 'Edit product';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => 'Shelf label',
            'sku' => 'SKU-100',
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form->statePath('data')->components([
            TextInput::make('name')->label('Name')->required(),
            TextInput::make('sku')->label('SKU')->live(),
            BarcodeDisplay::make('barcode')
                ->label('Barcode')
                ->type(BarcodeType::Code128)
                ->data(fn (Get $get): ?string => filled($get('sku')) ? (string) $get('sku') : null),
        ]);
    }
}
