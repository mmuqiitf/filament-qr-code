<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

/*
 * Filament 5.12 keys afterStateUpdated() hooks with its own counter instead
 * of appending. The typed-value hooks of gs1() and rejectWhen() go first, and
 * an afterStateUpdated() added after them must not replace them.
 */
class HookOrderComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<mixed> */
    public array $labelSeen = [];

    /** @var list<mixed> */
    public array $skuSeen = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                QrScanner::make('label')
                    ->gs1(fill: ['01' => 'gtin'])
                    ->afterStateUpdated(function (mixed $state): void {
                        $this->labelSeen[] = $state;
                    }),
                TextInput::make('gtin'),
                QrScanner::make('sku')
                    ->rejectWhen(fn ($state): bool => str_starts_with((string) $state, 'BAD'), 'Bad code.')
                    ->rejectWhen(fn ($state): bool => str_starts_with((string) $state, 'HOLD'), 'On hold.')
                    ->afterStateUpdated(function (mixed $state): void {
                        $this->skuSeen[] = $state;
                    })
                    ->normalizeUsing(fn (string $code): string => strtoupper($code)),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

it('keeps the gs1() typed-value hook when afterStateUpdated() is added after it', function (): void {
    Livewire::test(HookOrderComponent::class)
        ->set('data.label', '0109506000134352'.'17261231')
        ->assertSet('data.label', '(01)09506000134352(17)261231')
        ->assertSet('data.gtin', '09506000134352')
        ->assertSet('labelSeen', ['(01)09506000134352(17)261231']);
});

it('keeps every rejectWhen() typed-value hook when afterStateUpdated() is added after them', function (): void {
    Livewire::test(HookOrderComponent::class)
        ->set('data.sku', 'BAD-1')
        ->assertSet('data.sku', null)
        ->set('data.sku', 'HOLD-1')
        ->assertSet('data.sku', null)
        ->set('data.sku', 'prd-1')
        ->assertSet('data.sku', 'PRD-1')
        ->assertSet('skuSeen', [null, null, 'prd-1']);
});

it('runs the typed-value hooks first, then the caller hooks in order', function (): void {
    $field = QrScanner::make('code')
        ->afterStateUpdated(fn () => null)
        ->rejectWhen(fn (): bool => false, 'No.')
        ->gs1()
        ->rejectWhen(fn (): bool => false, 'Nope.')
        ->afterStateUpdated(fn () => null);

    $keys = array_keys((fn (): array => $this->afterStateUpdated)->call($field));

    expect($keys)->toHaveCount(5)
        ->and(array_slice($keys, 0, 3))->each->toBeString()
        ->and(array_slice($keys, 3))->each->toBeInt();
});
