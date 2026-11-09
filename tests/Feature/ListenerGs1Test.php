<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;

class ListenerGs1TestComponent extends Component implements HasForms
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
                QrHardwareScannerListener::make()
                    ->gs1(fill: ['01' => 'gtin', '17' => 'expires_at', '10' => 'batch', '3922' => 'price'], require: ['01', '17']),
                TextInput::make('gtin'),
                DatePicker::make('expires_at'),
                TextInput::make('batch'),
                TextInput::make('price'),
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

function listenerGs1Scan(mixed $test, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', $test->instance()->listenerKey(), 'handleGs1Scan', ['code' => $code]);
}

it('fills mapped fields from a keyboard-wedge burst with group separators', function (): void {
    Event::fake([QrCodeScanned::class]);
    $test = Livewire::test(ListenerGs1TestComponent::class);

    listenerGs1Scan($test, ']C1'."\x1D".'0109506000134352'.'17261231'.'10LOT-7'."\x1D".'3922'.'01999')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true
            && $result['code'] === '(01)09506000134352(17)261231(10)LOT-7(3922)01999'
            && $result['message'] === null)
        ->assertSet('data.gtin', '09506000134352')
        ->assertSet('data.expires_at', '2026-12-31')
        ->assertSet('data.batch', 'LOT-7')
        ->assertSet('data.price', '19.99');

    Event::assertDispatched(QrCodeScanned::class, fn (QrCodeScanned $event): bool => $event->code === '(01)09506000134352(17)261231(10)LOT-7(3922)01999'
        && $event->source === 'hardware');
});

it('rejects an invalid or incomplete label without filling anything', function (string $code, string $message): void {
    $test = Livewire::test(ListenerGs1TestComponent::class);

    listenerGs1Scan($test, $code)
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === $message)
        ->assertSet('data.gtin', null)
        ->assertSet('data.batch', null);
})->with([
    'bad check digit' => ['0109506000134353'.'17261231', 'Invalid check digit in AI (01)'],
    'missing expiry' => ['0109506000134352'.'10LOT-7', 'Missing AI (17) USE BY or EXPIRY'],
    'plain EAN-13' => ['4006381333931', 'Plain product barcode, not a GS1 label'],
]);

it('sends the gs1 flag and a component key to the browser', function (): void {
    $test = Livewire::test(ListenerGs1TestComponent::class);
    $listener = $test->instance()->form->getComponent(fn ($component): bool => $component instanceof QrHardwareScannerListener);

    expect($listener->getComponentScanPayload())
        ->toMatchArray(['gs1' => true, 'componentKey' => $test->instance()->listenerKey()])
        ->and(QrHardwareScannerListener::make()->getComponentScanPayload()['gs1'])->toBeFalse();
});

it('refuses a GS1 scan when gs1() is off', function (): void {
    $listener = QrHardwareScannerListener::make();

    expect($listener->isGs1())->toBeFalse();
});

it('renders an inline status line for GS1 rejections', function (): void {
    Livewire::test(ListenerGs1TestComponent::class)
        ->assertSeeHtml('data-qr-part="listener.status"');
});
