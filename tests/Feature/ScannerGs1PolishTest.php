<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;

class Gs1PolishScannerComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $keepMissing = false;

    /** @var list<string> */
    public array $scanned = [];

    /** @var list<?string> */
    public array $rejected = [];

    public int $checks = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                QrScanner::make('label')
                    ->gs1(
                        fill: ['01' => 'gtin', '17' => 'expires_at', '10' => 'batch', '21' => 'serial'],
                        keepMissing: fn (): bool => $this->keepMissing,
                    )
                    ->validateUsing(function (string $code): ?string {
                        $this->checks++;

                        return str_contains($code, '(10)HOLD') ? 'Batch on hold.' : null;
                    })
                    ->onScanned(function (string $code): void {
                        $this->scanned[] = $code;
                    })
                    ->onRejected(function (ScanResult $result): void {
                        $this->rejected[] = $result->message;
                    }),
                TextInput::make('gtin'),
                DatePicker::make('expires_at'),
                TextInput::make('batch'),
                TextInput::make('serial'),
                QrScanner::make('sku')
                    ->validateUsing(['regex:/^PRD-\d+$/']),
                QrScanner::make('ticker')
                    ->mode(ScanMode::Continuous)
                    ->validateUsing(['regex:/^PRD-\d+$/']),
            ])
            ->statePath('data');
    }

    public function keyOf(string $name): string
    {
        return $this->form->getComponent(fn ($component): bool => $component instanceof QrScanner && $component->getName() === $name)->getKey();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

class Gs1PolishListenerComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $keepMissing = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                QrHardwareScannerListener::make()
                    ->gs1(
                        fill: ['01' => 'gtin', '10' => 'batch', '21' => 'serial'],
                        require: ['01'],
                        keepMissing: fn (): bool => $this->keepMissing,
                        store: 'label',
                    ),
                TextInput::make('label'),
                TextInput::make('gtin'),
                TextInput::make('batch'),
                TextInput::make('serial'),
            ])
            ->statePath('data');
    }

    public function listenerKey(): string
    {
        return $this->form->getComponent(fn ($component): bool => $component instanceof QrHardwareScannerListener)->getKey();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

const POLISH_LOT = '(01)09506000134352(17)261231(10)LOT-1';
const POLISH_SERIAL = '(01)09506000134352(17)261231(21)SN-9';

function polishScan(mixed $test, string $field, string $code, string $source = 'hardware'): mixed
{
    return $test->call('callSchemaComponentMethod', $test->instance()->keyOf($field), 'handleScan', ['code' => $code, 'source' => $source]);
}

function polishListenerScan(mixed $test, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', $test->instance()->listenerKey(), 'handleGs1Scan', ['code' => $code]);
}

it('resets mapped fields the next accepted label does not carry', function (): void {
    $test = Livewire::test(Gs1PolishScannerComponent::class);

    polishScan($test, 'label', POLISH_LOT)->assertSet('data.batch', 'LOT-1');

    polishScan($test, 'label', POLISH_SERIAL)
        ->assertSet('data.serial', 'SN-9')
        ->assertSet('data.batch', null)
        ->assertSet('data.gtin', '09506000134352');
});

it('keeps mapped fields the label does not carry with keepMissing', function (): void {
    $test = Livewire::test(Gs1PolishScannerComponent::class)->set('keepMissing', true);

    polishScan($test, 'label', POLISH_LOT);

    polishScan($test, 'label', POLISH_SERIAL)
        ->assertSet('data.serial', 'SN-9')
        ->assertSet('data.batch', 'LOT-1');
});

it('leaves mapped fields untouched when a label is rejected', function (): void {
    $test = Livewire::test(Gs1PolishScannerComponent::class);

    polishScan($test, 'label', POLISH_LOT);

    polishScan($test, 'label', '(01)09506000134353(17)261231')
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', '09506000134352')
        ->assertSet('data.batch', 'LOT-1');
});

it('runs each check and callback once for a scan', function (): void {
    $test = Livewire::test(Gs1PolishScannerComponent::class);

    polishScan($test, 'label', POLISH_LOT)
        ->assertSet('checks', 1)
        ->assertSet('scanned', [POLISH_LOT]);
});

it('adds a scan rejection to the field error bag and clears it on the next accepted scan', function (): void {
    $test = Livewire::test(Gs1PolishScannerComponent::class);

    polishScan($test, 'label', '(01)09506000134353(17)261231')
        ->assertHasErrors(['data.label'])
        ->assertSee('Invalid check digit in AI (01)');

    polishScan($test, 'label', POLISH_LOT)
        ->assertHasNoErrors(['data.label'])
        ->assertDontSee('Invalid check digit in AI (01)');
});

it('keeps only the latest rejection in the error bag', function (): void {
    $test = Livewire::test(Gs1PolishScannerComponent::class);

    polishScan($test, 'label', '(01)09506000134353(17)261231');
    polishScan($test, 'label', '(01)09506000134352(17)261231(10)HOLD')
        ->assertSee('Batch on hold.')
        ->assertDontSee('Invalid check digit in AI (01)');

    expect($test->errors()->get('data.label'))->toBe(['Batch on hold.']);
});

it('adds rejections of a plain single-mode scanner to the error bag too', function (): void {
    $test = Livewire::test(Gs1PolishScannerComponent::class);

    polishScan($test, 'sku', 'XYZ')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false)
        ->assertHasErrors(['data.sku']);

    polishScan($test, 'sku', 'PRD-1')->assertHasNoErrors(['data.sku']);
});

it('leaves continuous-mode rejections to the status line', function (): void {
    polishScan(Livewire::test(Gs1PolishScannerComponent::class), 'ticker', 'XYZ')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false)
        ->assertHasNoErrors(['data.ticker']);
});

it('treats an invalid typed label like a rejected scan', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class]);

    Livewire::test(Gs1PolishScannerComponent::class)
        ->set('data.gtin', 'KEEP')
        ->set('data.label', '(01)09506000134353(17)261231')
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', 'KEEP')
        ->assertSet('rejected', ['Invalid check digit in AI (01)'])
        ->assertHasErrors(['data.label'])
        ->assertDispatched('qr-scan-rejected', message: 'Invalid check digit in AI (01)');

    Event::assertDispatched(ScanRejected::class, fn (ScanRejected $event): bool => $event->result->source?->value === 'manual');
    Event::assertNotDispatched(ScanAccepted::class);
});

it('runs validateUsing on a valid typed label', function (): void {
    Livewire::test(Gs1PolishScannerComponent::class)
        ->set('data.label', '(01)09506000134352(17)261231(10)HOLD')
        ->assertSet('data.label', null)
        ->assertSet('data.batch', null)
        ->assertSet('rejected', ['Batch on hold.'])
        ->assertHasErrors(['data.label'])
        ->assertSee('Batch on hold.');
});

it('fills, resets and accepts a valid typed label like a scan', function (): void {
    Event::fake([ScanAccepted::class]);

    Livewire::test(Gs1PolishScannerComponent::class)
        ->set('data.serial', 'OLD')
        ->set('data.label', '0109506000134352'.'17261231'.'10LOT-1')
        ->assertSet('data.label', POLISH_LOT)
        ->assertSet('data.batch', 'LOT-1')
        ->assertSet('data.expires_at', '2026-12-31')
        ->assertSet('data.serial', null)
        ->assertSet('scanned', [POLISH_LOT])
        ->assertSet('checks', 1)
        ->assertHasNoErrors(['data.label']);

    Event::assertDispatched(ScanAccepted::class, fn (ScanAccepted $event): bool => $event->result->source?->value === 'manual');
});

it('stores the bracketed label in the listener store field', function (): void {
    $test = Livewire::test(Gs1PolishListenerComponent::class);

    polishListenerScan($test, '0109506000134352'.'10LOT-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true)
        ->assertSet('data.label', '(01)09506000134352(10)LOT-1')
        ->assertSet('data.batch', 'LOT-1');
});

it('resets listener fields the next label does not carry, unless keepMissing', function (bool $keepMissing, ?string $batch): void {
    $test = Livewire::test(Gs1PolishListenerComponent::class)->set('keepMissing', $keepMissing);

    polishListenerScan($test, '0109506000134352'.'10LOT-1');

    polishListenerScan($test, '0109506000134352'.'21SN-9')
        ->assertSet('data.label', '(01)09506000134352(21)SN-9')
        ->assertSet('data.serial', 'SN-9')
        ->assertSet('data.batch', $batch);
})->with([
    'reset by default' => [false, null],
    'kept with keepMissing' => [true, 'LOT-1'],
]);

it('leaves the listener store and mapped fields untouched on a rejection', function (): void {
    $test = Livewire::test(Gs1PolishListenerComponent::class);

    polishListenerScan($test, '0109506000134352'.'10LOT-1');

    polishListenerScan($test, '0109506000134353')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false)
        ->assertSet('data.label', '(01)09506000134352(10)LOT-1')
        ->assertSet('data.batch', 'LOT-1');
});

it('rejects an empty listener store path up front', function (): void {
    QrHardwareScannerListener::make()->gs1(store: '');
})->throws(InvalidArgumentException::class);
