<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

class SequenceGs1TestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $keepMissing = false;

    public bool $sequenceLevelGs1 = false;

    /** @var list<string> */
    public array $saw = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $label = ScanStep::make('label');

        if (! $this->sequenceLevelGs1) {
            $label->gs1(
                fill: ['01' => 'gtin', '17' => 'expires_at', '10' => 'batch', '3103' => 'net_weight'],
                require: ['01', '17'],
                keepMissing: fn (): bool => $this->keepMissing,
            );
        }

        $sequence = QrScanSequence::make()
            ->statePath('receipt')
            ->steps([
                $label->validateUsing(function (string $code): void {
                    $this->saw[] = $code;
                }),
                ScanStep::make('confirm')->validateUsing(['regex:/^OK$/']),
            ]);

        if ($this->sequenceLevelGs1) {
            $sequence->gs1(
                fill: ['01' => 'gtin', '17' => 'expires_at'],
                require: ['01'],
            );
        }

        return $form
            ->schema([
                $sequence,
                TextInput::make('gtin'),
                DatePicker::make('expires_at'),
                TextInput::make('batch'),
                TextInput::make('net_weight'),
                TextInput::make('serial'),
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

class SequenceGs1DefaultTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                QrScanSequence::make()
                    ->statePath('receipt')
                    ->gs1(require: ['01'])
                    ->steps([
                        ScanStep::make('case'),
                        ScanStep::make('pallet')->gs1(require: ['00']),
                    ]),
                TextInput::make('gtin'),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

const SEQUENCE_GS1_GTIN = '09506000134352';
const SEQUENCE_GS1_BRACKETED = '(01)09506000134352(17)261231(10)ABC(3103)001250';
const SEQUENCE_GS1_RAW = '0109506000134352172612311'.'0ABC'."\x1D".'3103001250';

function sequenceGs1Scan(mixed $test, string $step, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', 'form.receipt', 'handleStepScan', ['step' => $step, 'code' => $code, 'source' => 'hardware']);
}

it('sends a sequence with gs1() steps through the server check', function (): void {
    $sequence = QrScanSequence::make()
        ->statePath('receipt')
        ->steps([ScanStep::make('label')->gs1(require: ['01'])]);

    expect($sequence->isGs1())->toBeFalse()
        ->and($sequence->getScanSteps()[0]->isGs1())->toBeTrue()
        ->and($sequence->getScanSteps()[0]->hasChecks())->toBeTrue()
        ->and($sequence->hasServerSequenceWork())->toBeTrue();
});

it('stores the normalized bracketed string and fills mapped fields from a step scan', function (string $raw): void {
    $test = Livewire::test(SequenceGs1TestComponent::class);

    sequenceGs1Scan($test, 'label', $raw)
        ->assertReturned(fn (array $result): bool => $result['passes'] === true
            && $result['code'] === SEQUENCE_GS1_BRACKETED
            && $result['step'] === 'label'
            && $result['next_step'] === 'confirm')
        ->assertSet('data.receipt.label', SEQUENCE_GS1_BRACKETED)
        ->assertSet('data.gtin', SEQUENCE_GS1_GTIN)
        ->assertSet('data.expires_at', '2026-12-31')
        ->assertSet('data.batch', 'ABC')
        ->assertSet('data.net_weight', '1.250')
        ->assertSet('data.serial', null);

    expect($test->instance()->saw)->toBe([SEQUENCE_GS1_BRACKETED]);
})->with([
    'bracketed' => [SEQUENCE_GS1_BRACKETED],
    'raw with group separator' => [SEQUENCE_GS1_RAW],
    'GS1-128 AIM prefix' => [']C1'.SEQUENCE_GS1_RAW],
]);

it('rejects an invalid GS1 step scan before any state is written', function (string $code, string $message): void {
    $test = Livewire::test(SequenceGs1TestComponent::class);

    sequenceGs1Scan($test, 'label', $code)
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === $message
            && $result['next_step'] === 'label')
        ->assertSet('data.receipt.label', null)
        ->assertSet('data.gtin', null)
        ->assertSet('data.expires_at', null)
        ->assertSet('data.batch', null);

    expect($test->instance()->saw)->toBe([]);
})->with([
    'bad check digit' => ['0109506000134353'.'17261231', 'Invalid check digit in AI (01)'],
    'missing expiry' => ['0109506000134352'.'10LOT-7', 'Missing AI (17) USE BY or EXPIRY'],
    'plain EAN-13' => ['4006381333931', 'Plain product barcode, not a GS1 label'],
]);

it('applies a sequence-level gs1() default and lets a step override it', function (): void {
    $test = Livewire::test(SequenceGs1DefaultTestComponent::class);
    $sequence = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);

    expect($sequence->isGs1())->toBeTrue()
        ->and($sequence->hasServerSequenceWork())->toBeTrue();

    sequenceGs1Scan($test, 'case', '(01)09506000134352')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true && $result['code'] === '(01)09506000134352');

    sequenceGs1Scan($test, 'pallet', '(01)09506000134352')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && str_contains((string) $result['message'], 'Missing AI (00)'));
});

it('clears mapped fields when a later label lacks their AI, unless keepMissing is on', function (): void {
    $test = Livewire::test(SequenceGs1TestComponent::class);

    sequenceGs1Scan($test, 'label', SEQUENCE_GS1_BRACKETED)
        ->assertSet('data.batch', 'ABC');

    sequenceGs1Scan($test, 'label', '(01)09506000134352(17)261231')
        ->assertSet('data.gtin', SEQUENCE_GS1_GTIN)
        ->assertSet('data.batch', null);

    $kept = Livewire::test(SequenceGs1TestComponent::class)->set('keepMissing', true);

    sequenceGs1Scan($kept, 'label', SEQUENCE_GS1_BRACKETED)
        ->assertSet('data.batch', 'ABC');

    sequenceGs1Scan($kept, 'label', '(01)09506000134352(17)261231')
        ->assertSet('data.batch', 'ABC');
});

it('enforces the GS1 rules on submit', function (): void {
    Livewire::test(SequenceGs1TestComponent::class)
        ->set('data.receipt', ['label' => 'NOPE', 'confirm' => 'OK'])
        ->call('submit')
        ->assertHasErrors(['data.receipt.label'])
        ->set('data.receipt', ['label' => SEQUENCE_GS1_BRACKETED, 'confirm' => 'OK'])
        ->call('submit')
        ->assertHasNoErrors();
});

it('rejects an unknown AI in a step fill or require list up front', function (): void {
    expect(fn () => ScanStep::make('label')->gs1(fill: ['9999' => 'x']))
        ->toThrow(InvalidArgumentException::class, '9999')
        ->and(fn () => QrScanSequence::make()->gs1(require: ['9999']))
        ->toThrow(InvalidArgumentException::class, '9999');
});
