<?php

declare(strict_types=1);

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

class ItemRulesFormComponent extends Component implements HasForms
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
                QrCollector::make('codes')
                    ->itemRules(['regex:/^PRD-\d+$/']),
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

it('passes a code when the collector has no item rules', function () {
    $collector = QrCollector::make('codes');

    expect($collector->checkCollectorItem('ANY'))->toBe(['passes' => true, 'message' => null])
        ->and($collector->getScanPayload()['hasItemRules'])->toBeFalse();
});

it('uses the latest itemRules list', function () {
    $collector = QrCollector::make('codes')
        ->itemRules(['regex:/^A/'])
        ->itemRules(fn (): array => ['regex:/^B/']);

    expect($collector->checkCollectorItem('B1')['passes'])->toBeTrue()
        ->and($collector->checkCollectorItem('A1')['passes'])->toBeFalse()
        ->and($collector->hasItemRules())->toBeTrue();
});

it('fails submit on the first collected code that breaks itemRules', function () {
    Livewire::test(ItemRulesFormComponent::class)
        ->set('data.codes', ['PRD-1', 'NOPE'])
        ->call('submit')
        ->assertHasErrors(['data.codes'])
        ->set('data.codes', ['PRD-1', 'PRD-2'])
        ->call('submit')
        ->assertHasNoErrors();
});

it('checks one code through the schema method', function () {
    $test = Livewire::test(ItemRulesFormComponent::class);
    $key = $test->instance()->form->getComponent('codes')->getKey();

    $test->call('callSchemaComponentMethod', $key, 'checkCollectorItem', ['code' => 'NOPE']);

    expect($test->effects['returns'][0])->toMatchArray(['passes' => false])
        ->and($test->effects['returns'][0]['message'])->not->toBeEmpty();

    $test->call('callSchemaComponentMethod', $key, 'checkCollectorItem', ['code' => 'PRD-4']);

    expect($test->effects['returns'][0])->toBe(['passes' => true, 'message' => null]);
});
