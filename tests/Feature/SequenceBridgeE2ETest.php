<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

class SequenceBridgeLivewireComponent extends Component implements HasForms
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
                    ->normalizeUsing(fn ($rawValue) => strtoupper(trim((string) $rawValue))),
                QrScanSequence::make(['step', 'employee'])
                    ->statePath('sequence')
                    ->normalizeStepUsing(fn ($rawValue) => strtoupper(trim((string) $rawValue))),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>{{ $this->form }}</div>
        BLADE;
    }
}

it('normalizes live scanner input via normalizeUsing', function () {
    Livewire::test(SequenceBridgeLivewireComponent::class)
        ->set('data.sku', '  abc-123  ')
        ->assertSet('data.sku', 'ABC-123');
});

it('bridges sequence scans into component state readable via helpers', function () {
    $test = Livewire::test(SequenceBridgeLivewireComponent::class)
        ->set('data.sequence', ['step' => '  s-001  ', 'employee' => 'e-42'])
        ->assertSet('data.sequence.step', '  s-001  ');

    /** @var SequenceBridgeLivewireComponent $livewire */
    $livewire = $test->instance();

    $sequence = null;
    foreach ($livewire->form->getComponents() as $component) {
        if ($component instanceof QrScanSequence) {
            $sequence = $component;
            break;
        }
    }

    expect($sequence)->not->toBeNull();

    // Component state is normalized on read.
    expect($sequence->getSequenceState())->toBe(['step' => 'S-001', 'employee' => 'E-42']);

    $merged = $sequence->mergeSequenceState($livewire->form->getState());

    expect($merged['step'])->toBe('S-001')
        ->and($merged['employee'])->toBe('E-42')
        ->and($sequence->isSequenceComplete($merged))->toBeTrue()
        ->and($sequence->getMissingSequenceKeys($merged))->toBe([]);
});

it('serves lazy modal QR images through the signed route', function () {
    $url = URL::signedRoute('filament-qr-code.image', [
        'data' => 'SIGNED-ROUTE-123',
        'size' => 120,
        'format' => 'svg',
    ]);

    $response = $this->get($url);

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');

    expect($response->getContent())->toContain('<svg');
});

class MismatchedPrefixSequenceComponent extends Component implements HasForms
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
                QrScanSequence::make(['step'])->statePathPrefix('order'),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>{{ $this->form }}</div>
        BLADE;
    }
}

it('warns loudly when the sequence prefix misses the form state path', function () {
    Livewire::test(SequenceBridgeLivewireComponent::class)
        ->assertDontSee('will be lost on submit');

    Livewire::test(MismatchedPrefixSequenceComponent::class)
        ->assertSee('will be lost on submit')
        ->assertSee('order');
});
