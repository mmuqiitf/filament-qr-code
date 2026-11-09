<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

class ScannerGs1TestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<string> */
    public array $rejectWhenSaw = [];

    public bool $clearOnReject = true;

    /** @var list<string> */
    public array $require = [];

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
                        fill: ['01' => 'gtin', '17' => 'expires_at', '10' => 'batch', '3103' => 'net_weight'],
                        require: fn (): array => $this->require,
                    )
                    ->clearOnReject(fn (): bool => $this->clearOnReject)
                    ->rejectWhen(function (string $state): bool {
                        $this->rejectWhenSaw[] = $state;

                        return str_contains($state, 'BLOCKED');
                    }, 'Batch is blocked.'),
                TextInput::make('gtin'),
                DatePicker::make('expires_at'),
                TextInput::make('batch'),
                TextInput::make('net_weight'),
                TextInput::make('serial'),
            ])
            ->statePath('data');
    }

    public function keyOf(string $name): string
    {
        return $this->form->getComponent(fn ($component): bool => method_exists($component, 'getName') && $component->getName() === $name)->getKey();
    }

    public function submit(): void
    {
        $this->form->getState();
    }

    /** Write state the way a tampered request would: no hooks, no scan checks. */
    public function forceLabel(string $label): void
    {
        $this->data['label'] = $label;
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

class NestedScannerGs1TestComponent extends Component implements HasForms
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
                Group::make([
                    QrScanner::make('label')->gs1(fill: ['01' => 'gtin', '17' => 'expires_at']),
                    TextInput::make('gtin'),
                    DatePicker::make('expires_at'),
                ])->statePath('line'),
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

const GS1_GTIN = '09506000134352';
const GS1_BRACKETED = '(01)09506000134352(17)261231(10)ABC(3103)001250';

function gs1Scan(mixed $test, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', $test->instance()->keyOf('label'), 'handleScan', ['code' => $code, 'source' => 'hardware']);
}

it('stores the normalized bracketed string and fills mapped fields from a raw scan', function (string $raw): void {
    $test = Livewire::test(ScannerGs1TestComponent::class);

    gs1Scan($test, $raw)
        ->assertReturned(fn (array $result): bool => $result['passes'] === true && $result['code'] === GS1_BRACKETED)
        ->assertSet('data.label', GS1_BRACKETED)
        ->assertSet('data.gtin', GS1_GTIN)
        ->assertSet('data.expires_at', '2026-12-31')
        ->assertSet('data.batch', 'ABC')
        ->assertSet('data.net_weight', '1.250')
        ->assertSet('data.serial', null);

    expect(Gs1::parse(GS1_BRACKETED)->bracketed())->toBe(GS1_BRACKETED);
})->with([
    'bracketed' => [GS1_BRACKETED],
    'raw with group separator' => ['0109506000134352172612311'.'0ABC'."\x1D".'3103001250'],
    'GS1-128 AIM prefix' => [']C10109506000134352172612311'.'0ABC'."\x1D".'3103001250'],
    'DataMatrix AIM prefix and leading FNC1' => [']d2'."\x1D".'0109506000134352172612311'.'0ABC'."\x1D".'3103001250'],
    'QR AIM prefix' => [']Q30109506000134352172612311'.'0ABC'."\x1D".'3103001250'],
]);

it('writes day 00 as the last day of the month', function (): void {
    $test = Livewire::test(ScannerGs1TestComponent::class);

    gs1Scan($test, '(01)09506000134352(17)260200')
        ->assertSet('data.expires_at', '2026-02-28');
});

it('rejects an invalid GS1 scan before any state is written', function (string $code, string $message): void {
    Event::fake([ScanAccepted::class, ScanRejected::class]);
    $test = Livewire::test(ScannerGs1TestComponent::class);

    gs1Scan($test, $code)
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === $message)
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', null)
        ->assertSet('data.expires_at', null)
        ->assertSet('data.batch', null)
        ->assertSet('rejectWhenSaw', []);

    Event::assertDispatched(ScanRejected::class, fn (ScanRejected $event): bool => $event->result->message === $message);
    Event::assertNotDispatched(ScanAccepted::class);
})->with([
    'bad check digit' => ['(01)09506000134353(17)261231', 'Invalid check digit in AI (01)'],
    'invalid date' => ['(01)09506000134352(17)261340', 'Invalid date in AI (17)'],
    'unknown AI' => ['(01)09506000134352(23)X', 'Unknown AI (23)'],
    'wrong length' => ['(01)0950600013435', 'Invalid length in AI (01)'],
    'bad characters' => ['(01)0950600013435A', 'Invalid characters in AI (01)'],
    'duplicate AI' => ['(10)A(10)B', 'AI (10) appears twice'],
    'malformed brackets' => ['(01', 'Not a GS1 label'],
    'plain EAN-13' => ['4006381333931', 'Plain product barcode, not a GS1 label'],
    'plain ITF-14' => ['10095060001347', 'Plain product barcode, not a GS1 label'],
    'plain UPC-A' => ['036000291452', 'Plain product barcode, not a GS1 label'],
    'EAN-13 AIM prefix' => [']E09506000134352', 'Plain product barcode, not a GS1 label'],
]);

it('keeps the previous scan and its filled fields when clearOnReject is off', function (): void {
    $test = Livewire::test(ScannerGs1TestComponent::class)->set('clearOnReject', false);

    gs1Scan($test, GS1_BRACKETED);
    gs1Scan($test, '(01)09506000134353')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false)
        ->assertSet('data.label', GS1_BRACKETED)
        ->assertSet('data.gtin', GS1_GTIN)
        ->assertSet('data.batch', 'ABC');
});

it('rejects a scan that misses a required AI, naming it with its dictionary title', function (): void {
    $test = Livewire::test(ScannerGs1TestComponent::class)->set('require', ['01', '17', '10']);

    gs1Scan($test, '(01)09506000134352(10)ABC')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === 'Missing AI (17) USE BY or EXPIRY')
        ->assertSet('data.label', null)
        ->assertSet('data.gtin', null);

    gs1Scan($test, GS1_BRACKETED)
        ->assertReturned(fn (array $result): bool => $result['passes'] === true);
});

it('runs the GS1 check before rejectWhen, which sees the bracketed string', function (): void {
    $test = Livewire::test(ScannerGs1TestComponent::class);

    gs1Scan($test, '0109506000134352172612311'.'0BLOCKED')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === 'Batch is blocked.')
        ->assertSet('data.gtin', null)
        ->assertSet('rejectWhenSaw', ['(01)09506000134352(17)261231(10)BLOCKED']);
});

it('translates GS1 rejections', function (): void {
    app()->setLocale('id');
    $test = Livewire::test(ScannerGs1TestComponent::class)->set('require', ['17']);

    gs1Scan($test, '(01)09506000134353')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'Digit pemeriksa AI (01) tidak valid');
    gs1Scan($test, '(01)09506000134352')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'AI (17) USE BY or EXPIRY tidak ada');
});

it('normalizes and fills a typed value, and clears an invalid one with a rejection event', function (): void {
    Livewire::test(ScannerGs1TestComponent::class)
        ->set('data.label', '0109506000134352172612311'.'0ABC')
        ->assertSet('data.label', '(01)09506000134352(17)261231(10)ABC')
        ->assertSet('data.gtin', GS1_GTIN)
        ->assertSet('data.expires_at', '2026-12-31')
        ->set('data.label', '(01)09506000134353')
        ->assertSet('data.label', null)
        ->assertDispatched('qr-scan-rejected', message: 'Invalid check digit in AI (01)');
});

it('enforces the GS1 rules on submit', function (): void {
    Livewire::test(ScannerGs1TestComponent::class)
        ->set('require', ['17'])
        ->call('forceLabel', '(01)09506000134353(17)261231')
        ->call('submit')
        ->assertHasErrors(['data.label'])
        ->call('forceLabel', '(01)09506000134352')
        ->call('submit')
        ->assertHasErrors(['data.label'])
        ->call('forceLabel', '4006381333931')
        ->call('submit')
        ->assertHasErrors(['data.label'])
        ->call('forceLabel', '(01)09506000134352(17)261231')
        ->call('submit')
        ->assertHasNoErrors();
});

it('fills sibling fields by relative path inside a nested schema', function (): void {
    $test = Livewire::test(NestedScannerGs1TestComponent::class);

    $test->call('callSchemaComponentMethod', $test->instance()->keyOf('label'), 'handleScan', ['code' => '0109506000134352'.'17261231', 'source' => 'camera'])
        ->assertSet('data.line.label', '(01)09506000134352(17)261231')
        ->assertSet('data.line.gtin', GS1_GTIN)
        ->assertSet('data.line.expires_at', '2026-12-31');
});

it('sends gs1 scans through the server check and tells the browser', function (): void {
    $scanner = QrScanner::make('label')->gs1();

    expect($scanner->isGs1())->toBeTrue()
        ->and($scanner->getScanChecksScanPayload()['serverCheck'])->toBeTrue()
        ->and($scanner->getComponentScanPayload()['gs1'])->toBeTrue()
        ->and(QrScanner::make('label')->getComponentScanPayload()['gs1'])->toBeFalse();
});

it('rejects an unknown AI in the fill map or require list up front', function (array $fill, array $require): void {
    QrScanner::make('label')->gs1(fill: $fill, require: $require);
})->with([
    'fill' => [['0' => 'x'], []],
    'require' => [[], ['9999']],
])->throws(InvalidArgumentException::class);
