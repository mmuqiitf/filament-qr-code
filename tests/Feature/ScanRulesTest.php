<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class ScanRulesLivewireComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function form(Form|Schema $form): Form|Schema
    {
        return $form
            ->schema([
                QrScanner::make('sku')
                    ->scanRules(['min:3'])
                    ->rejectWhen(
                        fn ($state) => str_starts_with((string) $state, 'BAD'),
                        'Bad code rejected.',
                    ),
                QrCollector::make('codes')->distinctItems(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>{{ $this->form }}</div>
        BLADE;
    }
}

it('rejects matching live scans immediately with a browser event', function () {
    Livewire::test(ScanRulesLivewireComponent::class)
        ->set('data.sku', 'BAD-001')
        ->assertDispatched('qr-scan-rejected')
        ->assertSet('data.sku', null)
        ->set('data.sku', 'GOOD-001')
        ->assertSet('data.sku', 'GOOD-001');
});

it('validates scanned values and distinct collector items on submit', function () {
    Livewire::test(ScanRulesLivewireComponent::class)
        ->set('data.sku', 'AB')
        ->set('data.codes', ['X-1', 'X-1'])
        ->call('submit')
        ->assertHasErrors(['data.sku', 'data.codes'])
        ->set('data.sku', 'ABC-1')
        ->set('data.codes', ['X-1', 'X-2'])
        ->call('submit')
        ->assertHasNoErrors();
});
