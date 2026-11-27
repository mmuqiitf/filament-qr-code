<?php

declare(strict_types=1);

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Workbench\App\Filament\Pages\ProductEdit;

class ProductEditPreviewComponent extends Component implements HasForms
{
    use InteractsWithForms;

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
        return (new ProductEdit)->form($form);
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

it('updates the product barcode when the SKU changes', function (): void {
    $test = Livewire::test(ProductEditPreviewComponent::class)
        ->assertSet('data.sku', 'SKU-100')
        ->assertSee('Code 128 barcode: SKU-100', false);

    $test->set('data.sku', 'SKU-200')
        ->assertSet('data.sku', 'SKU-200')
        ->assertSee('Code 128 barcode: SKU-200', false)
        ->assertDontSee('Code 128 barcode: SKU-100', false);
});
