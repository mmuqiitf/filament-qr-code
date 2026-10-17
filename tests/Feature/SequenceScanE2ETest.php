<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Closure;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Events\ScanSequenceCompleted;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;

class SequenceScanTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public int $submissions = 0;

    /** @var list<array<string, mixed>> */
    public array $completed = [];

    public static ?Closure $configure = null;

    public static bool $failSubmit = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $sequence = QrScanSequence::make()
            ->statePath('receipt')
            ->steps([
                ScanStep::make('po')->validateUsing(['regex:/^PO-\d+$/']),
                ScanStep::make('sku')->lookup(ScanCheckProduct::class, 'sku'),
                ScanStep::make('lot')->validateUsing(['regex:/^L\d{3}$/'])->optional(),
                ScanStep::make('serial')->validateUsing(['regex:/^SN\d+$/']),
            ])
            ->onCompleted(function (array $values): void {
                $this->completed[] = $values;
            })
            ->autoSubmit();

        if (self::$configure instanceof Closure) {
            (self::$configure)($sequence);
        }

        return $form->schema([$sequence])->statePath('data');
    }

    public function submit(): void
    {
        if (self::$failSubmit) {
            throw ValidationException::withMessages(['data.note' => 'Note is required.']);
        }

        $this->submissions++;
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

function sequenceScan(mixed $test, string $step, string $code): mixed
{
    return $test->call('callSchemaComponentMethod', 'form.receipt', 'handleStepScan', ['step' => $step, 'code' => $code, 'source' => 'hardware']);
}

beforeEach(function (): void {
    ScanCheckProduct::createTableWithProducts();
    SequenceScanTestComponent::$configure = null;
    SequenceScanTestComponent::$failSubmit = false;
});

it('invalid step 2 blocks advance; complete valid sequence calls submit once', function (): void {
    $test = Livewire::test(SequenceScanTestComponent::class);

    sequenceScan($test, 'po', 'PO-7')
        ->assertReturned(fn (array $result): bool => $result['passes'] && $result['step'] === 'po' && $result['next_step'] === 'sku');

    sequenceScan($test, 'sku', 'SKU-404')
        ->assertReturned(fn (array $result): bool => ! $result['passes']
            && $result['step'] === 'sku'
            && $result['next_step'] === 'sku'
            && $result['message'] === __('filament-qr-code::ui.record_not_found', ['code' => 'SKU-404']))
        ->assertSet('data.receipt.sku', null)
        ->assertSet('submissions', 0);

    sequenceScan($test, 'sku', 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] && $result['next_step'] === 'lot');
    sequenceScan($test, 'lot', 'L123');

    sequenceScan($test, 'serial', 'SN99')
        ->assertReturned(fn (array $result): bool => $result['passes']
            && $result['completed'] === true
            && $result['submitted'] === true
            && $result['next_step'] === null
            && $result['values'] === ['po' => 'PO-7', 'sku' => 'SKU-1', 'lot' => 'L123', 'serial' => 'SN99'])
        ->assertSet('submissions', 1)
        ->assertSet('data.receipt', ['po' => 'PO-7', 'sku' => 'SKU-1', 'lot' => 'L123', 'serial' => 'SN99'])
        ->assertSet('completed', [['po' => 'PO-7', 'sku' => 'SKU-1', 'lot' => 'L123', 'serial' => 'SN99']]);
});

it('fires step and completion events', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, ScanSequenceCompleted::class]);
    $test = Livewire::test(SequenceScanTestComponent::class);

    sequenceScan($test, 'po', 'bad');
    sequenceScan($test, 'po', 'PO-1');
    sequenceScan($test, 'sku', 'SKU-1');
    sequenceScan($test, 'serial', 'SN1');

    Event::assertDispatched(ScanRejected::class, fn (ScanRejected $event): bool => $event->field === 'po');
    Event::assertDispatchedTimes(ScanAccepted::class, 3);
    Event::assertDispatched(ScanSequenceCompleted::class, fn (ScanSequenceCompleted $event): bool => $event->values === ['po' => 'PO-1', 'sku' => 'SKU-1', 'serial' => 'SN1']);
});

it('rejects an unknown step', function (): void {
    sequenceScan(Livewire::test(SequenceScanTestComponent::class), 'nope', 'X')
        ->assertReturned(fn (array $result): bool => ! $result['passes'] && $result['message'] === __('filament-qr-code::ui.scan_rejected'));
});

it('does not submit without autoSubmit', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->autoSubmit(false);
    $test = Livewire::test(SequenceScanTestComponent::class);

    sequenceScan($test, 'po', 'PO-1');
    sequenceScan($test, 'sku', 'SKU-1');
    sequenceScan($test, 'serial', 'SN1')
        ->assertReturned(fn (array $result): bool => $result['completed'] && $result['submitted'] === null)
        ->assertSet('submissions', 0)
        ->assertCount('completed', 1);
});

it('skips an optional step when the code fits the next step', function (): void {
    $test = Livewire::test(SequenceScanTestComponent::class);

    sequenceScan($test, 'po', 'PO-1');
    sequenceScan($test, 'sku', 'SKU-1');
    sequenceScan($test, 'lot', 'SN5')
        ->assertReturned(fn (array $result): bool => $result['passes']
            && $result['step'] === 'serial'
            && $result['skipped'] === ['lot']
            && $result['completed'] === true)
        ->assertSet('data.receipt', ['po' => 'PO-1', 'sku' => 'SKU-1', 'serial' => 'SN5'])
        ->assertSet('submissions', 1);
});

it('rejects on the optional step when the code fits neither step', function (): void {
    $test = Livewire::test(SequenceScanTestComponent::class);

    sequenceScan($test, 'po', 'PO-1');
    sequenceScan($test, 'sku', 'SKU-1');
    sequenceScan($test, 'lot', 'junk')
        ->assertReturned(fn (array $result): bool => ! $result['passes'] && $result['step'] === 'lot' && $result['skipped'] === [])
        ->assertSet('submissions', 0);
});
