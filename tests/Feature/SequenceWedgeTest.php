<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Closure;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

class SequenceWedgeTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public int $submissions = 0;

    /** @var list<array<string, mixed>> */
    public array $completed = [];

    public static ?Closure $steps = null;

    public static ?Closure $configure = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $steps = self::$steps instanceof Closure ? (self::$steps)() : [
            ScanStep::make('po')->sticky(),
            ScanStep::make('sku'),
            ScanStep::make('lot'),
        ];

        $sequence = QrScanSequence::make()
            ->statePath('receipt')
            ->steps($steps)
            ->onCompleted(function (array $values): void {
                $this->completed[] = $values;
            });

        if (self::$configure instanceof Closure) {
            (self::$configure)($sequence);
        }

        return $form->schema([$sequence])->statePath('data');
    }

    public function submit(): void
    {
        $this->submissions++;
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

function wedgeScan(mixed $test, string $step, string $code, string $source = 'hardware'): mixed
{
    return $test->call('callSchemaComponentMethod', 'form.receipt', 'handleStepScan', ['step' => $step, 'code' => $code, 'source' => $source]);
}

function wedgeResult(mixed $test): array
{
    return $test->effects['returns'][0];
}

beforeEach(function (): void {
    SequenceWedgeTestComponent::$steps = null;
    SequenceWedgeTestComponent::$configure = null;
});

it('three bursts fill steps po, sku, lot in order; sticky po survives reset', function (): void {
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->continuous();
    $test = Livewire::test(SequenceWedgeTestComponent::class);

    expect(wedgeResult(wedgeScan($test, 'po', 'PO-1')))->toMatchArray(['passes' => true, 'next_step' => 'sku'])
        ->and(wedgeResult(wedgeScan($test, 'sku', 'SKU-1')))->toMatchArray(['passes' => true, 'next_step' => 'lot'])
        ->and(wedgeResult(wedgeScan($test, 'lot', 'L001')))->toMatchArray([
            'passes' => true,
            'completed' => true,
            'reset' => true,
            'next_step' => 'sku',
            'values' => ['po' => 'PO-1'],
        ]);

    $test->assertSet('data.receipt', ['po' => 'PO-1'])
        ->assertSet('completed', [['po' => 'PO-1', 'sku' => 'SKU-1', 'lot' => 'L001']]);

    expect(wedgeResult(wedgeScan($test, 'sku', 'SKU-2')))->toMatchArray(['next_step' => 'lot']);
    expect(wedgeResult(wedgeScan($test, 'lot', 'L002')))->toMatchArray(['completed' => true]);
    $test->assertSet('completed.1', ['po' => 'PO-1', 'sku' => 'SKU-2', 'lot' => 'L002']);
});

it('keeps sticky values after a submit reset', function (): void {
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->autoSubmit()->resetAfterSubmit();
    $test = Livewire::test(SequenceWedgeTestComponent::class);

    wedgeScan($test, 'po', 'PO-1');
    wedgeScan($test, 'sku', 'SKU-1');

    expect(wedgeResult(wedgeScan($test, 'lot', 'L001')))->toMatchArray(['submitted' => true, 'reset' => true, 'values' => ['po' => 'PO-1'], 'next_step' => 'sku']);
});

it('keeps sticky values when an invalid set restarts', function (): void {
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence
        ->validateAllUsing(fn (): string => 'Mismatch.')
        ->onInvalidSequence(InvalidSequenceBehavior::Restart);
    $test = Livewire::test(SequenceWedgeTestComponent::class);

    wedgeScan($test, 'po', 'PO-1');
    wedgeScan($test, 'sku', 'SKU-1');

    expect(wedgeResult(wedgeScan($test, 'lot', 'L001')))->toMatchArray(['reset' => true, 'values' => ['po' => 'PO-1'], 'next_step' => 'sku']);
    $test->assertSet('data.receipt', ['po' => 'PO-1']);
});

it('treats sticky steps as server work', function (): void {
    expect(QrScanSequence::make()->steps([ScanStep::make('po')->sticky(), ScanStep::make('sku')])->hasServerSequenceWork())->toBeTrue()
        ->and(QrScanSequence::make()->steps([ScanStep::make('po'), ScanStep::make('sku')])->hasServerSequenceWork())->toBeFalse()
        ->and(ScanStep::make('po')->sticky()->isSticky())->toBeTrue()
        ->and(ScanStep::make('po')->isSticky())->toBeFalse();
});
