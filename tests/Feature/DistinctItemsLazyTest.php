<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

class DistinctItemsFormComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('strict'),
                QrCollector::make('codes')
                    ->distinctItems(fn (Get $get): bool => (bool) $get('strict')),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

it('evaluates the distinctItems closure against live form state', function () {
    Livewire::test(DistinctItemsFormComponent::class)
        ->set('data.codes', ['A-1', 'A-1'])
        ->set('data.strict', false)
        ->call('submit')
        ->assertHasNoErrors()
        ->set('data.strict', true)
        ->call('submit')
        ->assertHasErrors(['data.codes']);
});

it('accepts a closure caption on QrCodeDisplay', function () {
    $display = QrCodeDisplay::make('preview')
        ->caption(fn (): string => 'LOT-42');

    expect($display->getCaption())->toBe('LOT-42');
});
