<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

class CollectorGs1TestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var list<string> */
    public array $require = ['01', '17'];

    /** @var array<int, mixed> */
    public array $itemRules = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $collector = QrCollector::make('codes')
            ->gs1(require: fn (): array => $this->require);

        if ($this->itemRules !== []) {
            $collector->itemRules($this->itemRules);
        }

        return $form
            ->schema([$collector])
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

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

const COLLECTOR_GS1_BRACKETED = '(01)09506000134352(17)261231(10)ABC(3103)001250';
const COLLECTOR_GS1_RAW = '0109506000134352172612311'.'0ABC'."\x1D".'3103001250';

function collectorGs1Check(mixed $test, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', $test->instance()->keyOf('codes'), 'checkCollectorItem', ['code' => $code]);
}

it('tells the browser a collector with gs1() needs a server item check', function (): void {
    $collector = QrCollector::make('codes')->gs1(require: ['01']);

    expect($collector->isGs1())->toBeTrue()
        ->and($collector->hasItemRules())->toBeFalse()
        ->and($collector->getScanPayload()['hasItemRules'])->toBeTrue();
});

it('normalizes a collected GS1 scan to the bracketed string', function (string $raw): void {
    $test = Livewire::test(CollectorGs1TestComponent::class);

    collectorGs1Check($test, $raw)
        ->assertReturned(fn (array $result): bool => $result['passes'] === true
            && $result['code'] === COLLECTOR_GS1_BRACKETED
            && $result['message'] === null);
})->with([
    'bracketed' => [COLLECTOR_GS1_BRACKETED],
    'raw with group separator' => [COLLECTOR_GS1_RAW],
    'GS1-128 AIM prefix' => [']C1'.COLLECTOR_GS1_RAW],
]);

it('applies normalizeWith before the GS1 check', function (): void {
    $collector = QrCollector::make('codes')
        ->normalizeWith(stripPrefix: ['PRE:'])
        ->gs1(require: ['01', '17']);

    expect($collector->checkCollectorItem('PRE:'.COLLECTOR_GS1_BRACKETED))
        ->toMatchArray(['passes' => true, 'message' => null, 'code' => COLLECTOR_GS1_BRACKETED])
        ->and($collector->checkCollectorItem('PRE:'.COLLECTOR_GS1_RAW))
        ->toMatchArray(['passes' => true, 'message' => null, 'code' => COLLECTOR_GS1_BRACKETED])
        ->and($collector->checkCollectorItem(COLLECTOR_GS1_BRACKETED))
        ->toMatchArray(['passes' => true, 'message' => null, 'code' => COLLECTOR_GS1_BRACKETED])
        ->and(QrCollector::make('codes')->gs1(require: ['01'])->checkCollectorItem('PRE:'.COLLECTOR_GS1_BRACKETED)['passes'])
        ->toBeFalse();
});

it('rejects an invalid or incomplete GS1 item before it can be added', function (string $code, string $message): void {
    $test = Livewire::test(CollectorGs1TestComponent::class);

    collectorGs1Check($test, $code)
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === $message);
})->with([
    'bad check digit' => ['0109506000134353'.'17261231', 'Invalid check digit in AI (01)'],
    'missing expiry' => ['0109506000134352'.'10LOT-7', 'Missing AI (17) USE BY or EXPIRY'],
    'plain EAN-13' => ['4006381333931', 'Plain product barcode, not a GS1 label'],
]);

it('runs GS1 before itemRules, which see the bracketed string', function (): void {
    $test = Livewire::test(CollectorGs1TestComponent::class);
    $test->instance()->itemRules = ['regex:/^\(01\)/'];
    $test->instance()->require = ['01'];

    collectorGs1Check($test, COLLECTOR_GS1_RAW)
        ->assertReturned(fn (array $result): bool => $result['passes'] === true && $result['code'] === COLLECTOR_GS1_BRACKETED);

    collectorGs1Check($test, '(10)LOT-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false);
});

it('fails submit on the first collected code that is not valid GS1', function (): void {
    Livewire::test(CollectorGs1TestComponent::class)
        ->set('data.codes', [COLLECTOR_GS1_BRACKETED, 'NOPE'])
        ->call('submit')
        ->assertHasErrors(['data.codes'])
        ->set('data.codes', [COLLECTOR_GS1_BRACKETED, COLLECTOR_GS1_RAW])
        ->call('submit')
        ->assertHasNoErrors();
});

it('rejects fill on the collector, because each item is a GS1 string in the list', function (): void {
    expect(fn () => QrCollector::make('codes')->gs1(fill: ['01' => 'gtin'], require: ['01']))
        ->toThrow(InvalidArgumentException::class, 'fill');
});

it('rejects an unknown AI in require up front', function (): void {
    expect(fn () => QrCollector::make('codes')->gs1(require: ['9999']))
        ->toThrow(InvalidArgumentException::class, '9999');
});
