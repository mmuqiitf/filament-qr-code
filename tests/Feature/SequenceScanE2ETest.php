<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Closure;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
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

    public static bool $keyless = false;

    public int $saves = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        $sequence = QrScanSequence::make();

        if (! self::$keyless) {
            $sequence->statePath('receipt');
        }

        $sequence
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

    public function save(): void
    {
        $this->form->getState();
        $this->saves++;
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
    SequenceScanTestComponent::$keyless = false;
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

function completeReceipt(mixed $test, string $serial = 'SN1'): mixed
{
    sequenceScan($test, 'po', 'PO-1');
    sequenceScan($test, 'sku', 'SKU-1');

    return sequenceScan($test, 'serial', $serial);
}

it('keeps the values and jumps to the failing step when the whole set is invalid', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence
        ->validateAllUsing(fn (array $values): array => $values['serial'] === 'SN1' ? ['sku' => 'Serial does not match SKU.'] : []);
    $test = Livewire::test(SequenceScanTestComponent::class);

    completeReceipt($test)
        ->assertReturned(fn (array $result): bool => ! $result['passes']
            && $result['completed'] === false
            && $result['invalid_step'] === 'sku'
            && $result['next_step'] === 'sku'
            && $result['message'] === 'Serial does not match SKU.')
        ->assertSet('data.receipt', ['po' => 'PO-1', 'sku' => 'SKU-1', 'serial' => 'SN1'])
        ->assertSet('submissions', 0);

    sequenceScan($test, 'serial', 'SN2')
        ->assertReturned(fn (array $result): bool => $result['completed'] && $result['submitted'])
        ->assertSet('submissions', 1);
});

it('validates the whole set with keyed and unkeyed rules', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence
        ->validateAllUsing(['po' => 'different:serial', 'not_in:SN9']);

    completeReceipt(Livewire::test(SequenceScanTestComponent::class), 'SN9')
        ->assertReturned(fn (array $result): bool => ! $result['passes'] && $result['invalid_step'] === 'serial');
});

it('restarts the sequence on an invalid set when configured', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence
        ->validateAllUsing(fn (): string => 'Mismatch.')
        ->onInvalidSequence(InvalidSequenceBehavior::Restart);

    completeReceipt(Livewire::test(SequenceScanTestComponent::class))
        ->assertReturned(fn (array $result): bool => $result['invalid_step'] === 'serial'
            && $result['next_step'] === 'po'
            && $result['reset'] === true
            && $result['values'] === [])
        ->assertSet('data.receipt', []);
});

it('reads the invalid behaviour from config', function (): void {
    config()->set('qr-code.sequence.on_invalid', 'restart');
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->validateAllUsing(fn (): string => 'Mismatch.');

    completeReceipt(Livewire::test(SequenceScanTestComponent::class))
        ->assertReturned(fn (array $result): bool => $result['next_step'] === 'po');
});

it('resets after a successful submit when asked', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->resetAfterSubmit();
    $test = Livewire::test(SequenceScanTestComponent::class);

    completeReceipt($test)
        ->assertReturned(fn (array $result): bool => $result['submitted'] && $result['reset'] && $result['next_step'] === 'po')
        ->assertSet('data.receipt', []);

    completeReceipt($test)->assertSet('submissions', 2);
});

it('starts over after each completion in continuous mode', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->autoSubmit(false)->continuous();
    $test = Livewire::test(SequenceScanTestComponent::class);

    completeReceipt($test)
        ->assertReturned(fn (array $result): bool => $result['completed'] && $result['reset'] && $result['next_step'] === 'po')
        ->assertSet('data.receipt', [])
        ->assertCount('completed', 1);
});

it('keeps the values and reports when the submit fails validation', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->resetAfterSubmit();
    SequenceScanTestComponent::$failSubmit = true;

    completeReceipt(Livewire::test(SequenceScanTestComponent::class))
        ->assertReturned(fn (array $result): bool => $result['completed'] && $result['submitted'] === false && $result['reset'] === false)
        ->assertSet('data.receipt', ['po' => 'PO-1', 'sku' => 'SKU-1', 'serial' => 'SN1'])
        ->assertHasErrors(['data.note']);
});

it('reads autoSubmit and resetAfterSubmit from config', function (): void {
    config()->set('qr-code.sequence.auto_submit', true);
    config()->set('qr-code.sequence.reset_after_submit', true);
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->autoSubmit(null);

    completeReceipt(Livewire::test(SequenceScanTestComponent::class))
        ->assertReturned(fn (array $result): bool => $result['submitted'] && $result['reset']);
});

it('asks the browser to use the server path only when there is server work', function (): void {
    $plain = QrScanSequence::make()->statePath('receipt')->fields(['po', 'sku']);
    $checked = QrScanSequence::make()->statePath('receipt')->steps([ScanStep::make('po')->validateUsing(['required'])]);
    $keyless = QrScanSequence::make()->steps([ScanStep::make('po')->validateUsing(['required'])]);

    $test = Livewire::test(SequenceScanTestComponent::class);
    $schema = $test->instance()->form;

    foreach ([$plain, $checked, $keyless] as $sequence) {
        $sequence->container($schema);
    }

    expect($plain->getComponentScanPayload()['serverCheck'])->toBeFalse()
        ->and($checked->getComponentScanPayload())->toMatchArray(['serverCheck' => true, 'componentKey' => 'form.receipt'])
        ->and($keyless->getComponentScanPayload())->toMatchArray(['serverCheck' => true, 'componentKey' => 'form.qr-scan-sequence-po']);
});

it('re-runs the step checks on a normal submit', function (): void {
    Livewire::test(SequenceScanTestComponent::class)
        ->set('data.receipt', ['po' => 'PO-1', 'sku' => 'SKU-404', 'serial' => 'SN1'])
        ->call('save')
        ->assertHasErrors(['data.receipt.sku'])
        ->assertSet('saves', 0)
        ->set('data.receipt', ['po' => 'PO-1', 'sku' => 'SKU-1', 'serial' => 'SN1'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saves', 1);
});

it('re-runs the whole-set check on a normal submit', function (): void {
    SequenceScanTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence
        ->validateAllUsing(fn (array $values): ?string => $values['serial'] === 'SN1' ? 'Mismatch.' : null);

    Livewire::test(SequenceScanTestComponent::class)
        ->set('data.receipt', ['po' => 'PO-1', 'sku' => 'SKU-1', 'serial' => 'SN1'])
        ->call('save')
        ->assertHasErrors(['data.receipt.serial' => 'Mismatch.'])
        ->assertSet('saves', 0);
});

it('leaves an empty optional step and missing steps to the form', function (): void {
    Livewire::test(SequenceScanTestComponent::class)
        ->set('data.receipt', ['po' => 'PO-1'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saves', 1);
});

it('checks a typed correction like a scan', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class]);
    $test = Livewire::test(SequenceScanTestComponent::class);

    sequenceScan($test, 'po', 'PO-1');
    $test->call('callSchemaComponentMethod', 'form.receipt', 'handleStepScan', ['step' => 'po', 'code' => 'oops', 'source' => 'manual'])
        ->assertReturned(fn (array $result): bool => ! $result['passes'] && $result['source'] === 'manual')
        ->assertSet('data.receipt.po', 'PO-1');

    Event::assertDispatched(ScanRejected::class, fn (ScanRejected $event): bool => $event->result->source === ScanInput::Manual);
});

it('does not skip an optional step on a typed correction', function (): void {
    $test = Livewire::test(SequenceScanTestComponent::class);

    $test->call('callSchemaComponentMethod', 'form.receipt', 'handleStepScan', ['step' => 'lot', 'code' => 'SN5', 'source' => 'manual'])
        ->assertReturned(fn (array $result): bool => ! $result['passes'] && $result['step'] === 'lot' && $result['skipped'] === []);
});

it('gives a checked sequence without a key an automatic key and warns in local dev', function (): void {
    SequenceScanTestComponent::$keyless = true;
    app()->detectEnvironment(fn (): string => 'local');
    Log::spy();

    $test = Livewire::test(SequenceScanTestComponent::class);
    $sequence = collect($test->instance()->form->getFlatComponents())->first(fn ($component): bool => $component instanceof QrScanSequence);
    $key = $sequence->getKey();

    expect($key)->toBe('form.qr-scan-sequence-po-sku-lot-serial')
        ->and($sequence->getComponentScanPayload())->toMatchArray(['serverCheck' => true, 'componentKey' => $key]);

    $test->call('callSchemaComponentMethod', $key, 'handleStepScan', ['step' => 'po', 'code' => 'PO-1', 'source' => 'hardware'])
        ->assertReturned(fn (array $result): bool => $result['passes'])
        ->assertSet('data.po', 'PO-1');

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message): bool => str_contains($message, 'QrScanSequence'));
});

it('keeps plain keyless sequences without a key', function (): void {
    $sequence = QrScanSequence::make()->fields(['po', 'sku']);
    $sequence->container(Livewire::test(SequenceScanTestComponent::class)->instance()->form);

    expect($sequence->getKey())->toBeNull();
});
