<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Closure;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\OptionsSync;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

class RuntimeControlTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public int $quantity = 2;

    public string $mode = 'single';

    public static ?Closure $steps = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $steps = self::$steps instanceof Closure ? (self::$steps)($this) : [
            ScanStep::make('po')->validateUsing(['regex:/^PO-\d+$/'])->sticky(),
            ScanStep::make('sku')->validateUsing(['regex:/^PRD-\d+$/']),
            ScanStep::make('lot')->validateUsing(['regex:/^LOT-[A-Z0-9]+$/'])->optional(),
            ScanStep::make('serials')->validateUsing(['regex:/^SN-[A-Z0-9-]+$/'])->repeat(fn (): int => $this->quantity),
        ];

        return $form
            ->statePath('data')
            ->components([
                QrScanner::make('sku')->mode(fn (): ScanMode => ScanMode::from($this->mode)),
                QrScanSequence::make()->statePath('receipt')->steps($steps),
            ]);
    }

    public function sequence(): QrScanSequence
    {
        /** @var QrScanSequence */
        return $this->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);
    }

    public function goTo(string $step): void
    {
        $this->sequence()->goToStep($step);
    }

    public function resetReceipt(bool $keepSticky = true): void
    {
        $this->sequence()->resetSequence($keepSticky);
    }

    public function setRepeat(string $step, ?int $count): void
    {
        $this->sequence()->setRepeatCount($step, $count);
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

function controlScan(mixed $test, string $step, string $code, string $source = 'hardware'): array
{
    $test->call('callSchemaComponentMethod', 'form.receipt', 'handleStepScan', ['step' => $step, 'code' => $code, 'source' => $source]);

    return $test->effects['returns'][0];
}

beforeEach(function (): void {
    RuntimeControlTestComponent::$steps = null;
});

it('runs setUp and configureUsing from make()', function (): void {
    QrScanSequence::configureUsing(fn (QrScanSequence $sequence) => $sequence->editable(false));

    try {
        expect(QrScanSequence::make(['a'])->isEditable())->toBeFalse();
    } finally {
        QrScanSequence::configureUsing(fn () => null);
    }

    Livewire::test(RuntimeControlTestComponent::class)->assertSet('data.receipt', []);
});

it('sends the options sync mode from config, with a per-field override', function (): void {
    expect(QrScanner::make('a')->getScanPayload()['syncOptions'])->toBe('morph')
        ->and(QrScanSequence::make(['a'])->getScanPayload()['syncOptions'])->toBe('morph');

    config()->set('qr-code.scan.sync_options', 'refresh');

    expect(QrScanner::make('a')->getScanPayload()['syncOptions'])->toBe('refresh')
        ->and(QrScanner::make('a')->syncOptions(OptionsSync::Morph)->getScanPayload()['syncOptions'])->toBe('morph')
        ->and(QrScanner::make('a')->syncOptions(fn (): string => 'morph')->getScanPayload()['syncOptions'])->toBe('morph');

    config()->set('qr-code.scan.sync_options', 'nonsense');

    expect(QrScanner::make('a')->getScanPayload()['syncOptions'])->toBe('morph');
});

it('renders the options on an attribute Livewire can morph', function (): void {
    $html = Livewire::test(RuntimeControlTestComponent::class)->html();

    expect($html)->toContain('data-qr-scanner')
        ->and(substr_count($html, 'data-qr-options='))->toBe(2)
        ->and($html)->toMatch('/data-qr-options="[^"]*&quot;mode&quot;:&quot;single&quot;/');

    // The sequence options sit outside its wire:ignore element.
    expect($html)->toMatch('/<div[^>]*data-qr-options="[^"]*"[^>]*>\s*<div[^>]*x-data="qrScanSequence/');
});

it('re-renders changed scanner options into the attribute and keeps x-data constant', function (): void {
    $test = Livewire::test(RuntimeControlTestComponent::class);
    preg_match('/x-data="(qrScanner\([^"]*)"/', $test->html(), $before);

    $test->set('mode', 'continuous');
    preg_match('/x-data="(qrScanner\([^"]*)"/', $test->html(), $after);

    // A changed x-data would make Alpine re-create the scanner on morph.
    expect($test->html())->toMatch('/data-qr-options="[^"]*&quot;mode&quot;:&quot;continuous&quot;/')
        ->and($after[1])->toBe($before[1]);
});

it('changing quantity updates repeat k/n', function (): void {
    $test = Livewire::test(RuntimeControlTestComponent::class);
    $repeatOf = fn (): ?int => collect($test->instance()->sequence()->getStepsPayload())->firstWhere('key', 'serials')['repeat'];

    expect($repeatOf())->toBe(2);

    $test->set('quantity', 5);

    expect($repeatOf())->toBe(5)
        ->and($test->html())->toContain('&quot;repeat&quot;:5');
});

it('sends the server values and step activity in the payload', function (): void {
    $test = Livewire::test(RuntimeControlTestComponent::class);
    controlScan($test, 'po', 'PO-1');

    $payload = $test->instance()->sequence()->getComponentScanPayload();

    expect($payload['values'])->toBe(['po' => 'PO-1'])
        ->and(collect($payload['steps'])->pluck('active')->all())->toBe([true, true, true, true]);
});

it('optional lot skips into serials', function (): void {
    $test = Livewire::test(RuntimeControlTestComponent::class);
    controlScan($test, 'po', 'PO-1');
    controlScan($test, 'sku', 'PRD-1');

    $result = controlScan($test, 'lot', 'SN-1');

    expect($result)->toMatchArray(['passes' => true, 'step' => 'serials', 'next_step' => 'serials', 'skipped' => ['lot']])
        ->and($result['values'])->toBe(['po' => 'PO-1', 'sku' => 'PRD-1', 'serials' => ['SN-1']]);
    $test->assertSet('data.receipt.serials', ['SN-1']);
});

it('still rejects at the optional step when the repeat step rejects the code', function (): void {
    $test = Livewire::test(RuntimeControlTestComponent::class);
    controlScan($test, 'po', 'PO-1');
    controlScan($test, 'sku', 'PRD-1');

    expect(controlScan($test, 'lot', 'NOPE'))->toMatchArray(['passes' => false, 'step' => 'lot', 'next_step' => 'lot']);
});

it('skips a step whose when() condition is false', function (): void {
    RuntimeControlTestComponent::$steps = fn (): array => [
        ScanStep::make('sku'),
        ScanStep::make('batch')->when(fn (array $values): bool => str_starts_with($values['sku'] ?? '', 'BATCH-')),
        ScanStep::make('box'),
    ];
    $test = Livewire::test(RuntimeControlTestComponent::class);

    expect(controlScan($test, 'sku', 'PRD-1'))->toMatchArray(['next_step' => 'box']);
    expect(controlScan($test, 'box', 'BOX-1'))->toMatchArray(['completed' => true]);

    $test->set('data.receipt', []);

    expect(controlScan($test, 'sku', 'BATCH-1'))->toMatchArray(['next_step' => 'batch']);
});

it('rejects a scan aimed at an inactive step and points at the next active one', function (): void {
    RuntimeControlTestComponent::$steps = fn (): array => [
        ScanStep::make('sku'),
        ScanStep::make('batch')->when(fn (): bool => false),
        ScanStep::make('box'),
    ];
    $test = Livewire::test(RuntimeControlTestComponent::class);

    expect(controlScan($test, 'batch', 'B-1'))->toMatchArray([
        'passes' => false,
        'step' => 'batch',
        'next_step' => 'sku',
        'message' => __('filament-qr-code::ui.step_inactive'),
    ]);
});

it('drops the value of a step that turned inactive and reports it inactive', function (): void {
    RuntimeControlTestComponent::$steps = fn (): array => [
        ScanStep::make('sku'),
        ScanStep::make('batch')->optional()->when(fn (array $values): bool => str_starts_with($values['sku'] ?? '', 'BATCH-')),
        ScanStep::make('box'),
    ];
    $test = Livewire::test(RuntimeControlTestComponent::class)
        ->set('data.receipt', ['sku' => 'PRD-1', 'batch' => 'B-1']);

    $sequence = $test->instance()->sequence();

    expect(collect($sequence->getStepsPayload())->pluck('active', 'key')->all())->toBe(['sku' => true, 'batch' => false, 'box' => true])
        ->and(controlScan($test, 'box', 'BOX-1'))->toMatchArray(['completed' => true, 'values' => ['sku' => 'PRD-1', 'box' => 'BOX-1']]);
});

it('treats a when() closure that throws as active', function (): void {
    $step = ScanStep::make('batch')->when(fn (): bool => throw new \RuntimeException('boom'));
    RuntimeControlTestComponent::$steps = fn (): array => [ScanStep::make('sku'), $step];
    $test = Livewire::test(RuntimeControlTestComponent::class);

    expect(controlScan($test, 'sku', 'PRD-1'))->toMatchArray(['next_step' => 'batch']);
});

it('does not require an inactive step on submit', function (): void {
    RuntimeControlTestComponent::$steps = fn (): array => [
        ScanStep::make('sku'),
        ScanStep::make('batch')->when(fn (): bool => false),
    ];
    $test = Livewire::test(RuntimeControlTestComponent::class)->set('data.receipt', ['sku' => 'PRD-1']);

    expect($test->instance()->sequence()->getMissingSequenceKeys())->toBe([])
        ->and($test->instance()->sequence()->allRequiredFilled(['sku' => 'PRD-1']))->toBeTrue();
});

it('moves the browser to a step from page code', function (): void {
    Livewire::test(RuntimeControlTestComponent::class)
        ->call('goTo', 'lot')
        ->assertDispatched('qr-sequence-go-to', field: 'form.receipt', step: 'lot');
});

it('refuses to go to an unknown step', function (): void {
    Livewire::test(RuntimeControlTestComponent::class)->call('goTo', 'nope');
})->throws(InvalidArgumentException::class);

it('resets the sequence from page code, keeping sticky steps', function (): void {
    $test = Livewire::test(RuntimeControlTestComponent::class);
    controlScan($test, 'po', 'PO-1');
    controlScan($test, 'sku', 'PRD-1');

    $test->call('resetReceipt')
        ->assertSet('data.receipt', ['po' => 'PO-1'])
        ->assertDispatched('qr-sequence-reset', field: 'form.receipt', values: ['po' => 'PO-1'], fromServer: true);

    $test->call('resetReceipt', false)->assertSet('data.receipt', []);
});

it('overrides a repeat count from page code across requests', function (): void {
    $test = Livewire::test(RuntimeControlTestComponent::class);
    controlScan($test, 'po', 'PO-1');
    controlScan($test, 'sku', 'PRD-1');

    $test->call('setRepeat', 'serials', 1)->assertDispatched('qr-sequence-refresh', field: 'form.receipt');

    expect(controlScan($test, 'serials', 'SN-1'))->toMatchArray(['completed' => true]);

    $test->call('setRepeat', 'serials', null)->call('resetReceipt');
    controlScan($test, 'sku', 'PRD-1');

    expect(controlScan($test, 'serials', 'SN-1'))->toMatchArray(['next_step' => 'serials']);
});

it('refuses a repeat count for a step that does not repeat', function (): void {
    Livewire::test(RuntimeControlTestComponent::class)->call('setRepeat', 'sku', 2);
})->throws(InvalidArgumentException::class);
