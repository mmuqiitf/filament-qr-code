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

class HandleScanTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $scannerDisabled = false;

    /** @var list<string> */
    public array $normalized = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                QrScanner::make('sku')
                    ->disabled(fn (): bool => $this->scannerDisabled)
                    ->afterStateUpdated(fn (?string $state) => $this->normalized[] = (string) $state),
                TextInput::make('note'),
            ])
            ->statePath('data');
    }

    public function keyOf(string $name): string
    {
        return $this->form->getComponent(fn ($component): bool => method_exists($component, 'getName') && $component->getName() === $name)->getKey();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

it('accepts a scan through callSchemaComponentMethod and sets state', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => '  SKU-1 ', 'source' => 'hardware'])
        ->assertReturned([
            'passes' => true,
            'code' => 'SKU-1',
            'source' => 'hardware',
            'message' => null,
            'record_key' => null,
        ])
        ->assertSet('data.sku', 'SKU-1')
        ->assertSet('normalized', ['SKU-1']);
});

it('maps an unknown source to camera', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => 'A', 'source' => 'bogus'])
        ->assertReturned(fn (array $result): bool => $result['source'] === 'camera');
});

it('rejects an empty code with a translated message and leaves state alone', function (string $locale, string $message): void {
    app()->setLocale($locale);
    $test = Livewire::test(HandleScanTestComponent::class);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => '   ', 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === $message)
        ->assertSet('data.sku', null);
})->with([
    'en' => ['en', 'The scanned code is empty.'],
    'id' => ['id', 'Kode yang dipindai kosong.'],
]);

it('rejects scans on a disabled field', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class)->set('scannerDisabled', true);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('sku'), 'handleScan', ['code' => 'SKU-1', 'source' => 'camera'])
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === __('filament-qr-code::ui.scan_rejected'))
        ->assertSet('data.sku', null);
});

it('returns null for keys that are not registered scanner fields', function (string $key): void {
    $test = Livewire::test(HandleScanTestComponent::class);
    $key = $key === 'note' ? $test->instance()->keyOf('note') : $key;

    $test->call('callSchemaComponentMethod', $key, 'handleScan', ['code' => 'SKU-1', 'source' => 'camera'])
        ->assertReturned(null)
        ->assertSet('data.sku', null);
})->with([
    'unknown key' => ['form.data.missing'],
    'non-scanner field' => ['note'],
]);

it('reports no server checks when nothing is configured', function (): void {
    expect(QrScanner::make('sku')->hasServerScanChecks())->toBeFalse();
});
it('renders the component key and server check flag into the scanner payload', function (): void {
    $test = Livewire::test(HandleScanTestComponent::class);
    $key = $test->instance()->keyOf('sku');

    $scanner = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrScanner);

    expect($scanner->getScanPayload())
        ->toHaveKey('componentKey', $key)
        ->toHaveKey('serverCheck', false);
});

it('omits the component key when the scanner is not mounted', function (): void {
    expect(QrScanner::make('sku')->getScanPayload())
        ->not->toHaveKey('componentKey')
        ->toHaveKey('serverCheck', false);
});
