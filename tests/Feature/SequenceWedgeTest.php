<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Closure;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Enums\InvalidSequenceBehavior;
use Mmuqiitf\FilamentQrCode\Enums\RepeatComplete;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;
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

    public function validateForm(): void
    {
        $this->form->getState();
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

function bomSteps(Closure $configureParts): Closure
{
    return fn (): array => [
        ScanStep::make('order'),
        $configureParts(ScanStep::make('parts')->validateUsing(['regex:/^P\d+$/'])),
        ScanStep::make('box'),
    ];
}

it('collects repeat(n) codes into a list and advances when full', function (): void {
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(3));
    $test = Livewire::test(SequenceWedgeTestComponent::class);

    wedgeScan($test, 'order', 'WO-1');

    expect(wedgeResult(wedgeScan($test, 'parts', 'P1')))->toMatchArray(['passes' => true, 'next_step' => 'parts', 'values' => ['order' => 'WO-1', 'parts' => ['P1']]])
        ->and(wedgeResult(wedgeScan($test, 'parts', 'X9')))->toMatchArray(['passes' => false, 'next_step' => 'parts'])
        ->and(wedgeResult(wedgeScan($test, 'parts', 'P2')))->toMatchArray(['next_step' => 'parts'])
        ->and(wedgeResult(wedgeScan($test, 'parts', 'P3')))->toMatchArray(['next_step' => 'box', 'values' => ['order' => 'WO-1', 'parts' => ['P1', 'P2', 'P3']]]);

    expect(wedgeResult(wedgeScan($test, 'box', 'BOX-1')))->toMatchArray(['completed' => true]);
    $test->assertSet('completed.0.parts', ['P1', 'P2', 'P3']);
});

it('rejects a duplicate inside a repeat step unless allowed', function (): void {
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(3));
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'parts', 'P1');

    expect(wedgeResult(wedgeScan($test, 'parts', 'P1')))->toMatchArray([
        'passes' => false,
        'message' => __('filament-qr-code::ui.duplicate_in_step', ['code' => 'P1']),
    ]);

    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(3)->allowDuplicates());
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'parts', 'P1');

    expect(wedgeResult(wedgeScan($test, 'parts', 'P1')))->toMatchArray(['passes' => true, 'values' => ['parts' => ['P1', 'P1']]]);
});

it('reads the repeat count from a closure over the other steps', function (): void {
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(fn (array $values): int => ($values['order'] ?? null) === 'WO-2' ? 2 : 5));
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'order', 'WO-2');
    wedgeScan($test, 'parts', 'P1');

    expect(wedgeResult(wedgeScan($test, 'parts', 'P2')))->toMatchArray(['next_step' => 'box']);
});

it('waits on a full step when repeat_complete is wait and rejects more codes', function (): void {
    config()->set('qr-code.sequence.repeat_complete', 'wait');
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(2));
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'parts', 'P1');

    expect(wedgeResult(wedgeScan($test, 'parts', 'P2')))->toMatchArray(['passes' => true, 'next_step' => 'parts'])
        ->and(wedgeResult(wedgeScan($test, 'parts', 'P3')))->toMatchArray([
            'passes' => false,
            'message' => __('filament-qr-code::ui.repeat_full', ['count' => 2]),
        ]);
});

it('lets a step override the repeat completion from config', function (): void {
    config()->set('qr-code.sequence.repeat_complete', 'wait');
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(1)->onRepeatComplete(RepeatComplete::Advance));
    $test = Livewire::test(SequenceWedgeTestComponent::class);

    expect(wedgeResult(wedgeScan($test, 'parts', 'P1')))->toMatchArray(['next_step' => 'box']);
});

it('ends an open-ended repeat with the step finish code without storing it', function (): void {
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat()->finishWith('DONE'));
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'order', 'WO-1');

    foreach (['P1', 'P2', 'P3', 'P4'] as $code) {
        expect(wedgeResult(wedgeScan($test, 'parts', $code)))->toMatchArray(['passes' => true, 'next_step' => 'parts']);
    }

    expect(wedgeResult(wedgeScan($test, 'parts', 'DONE')))->toMatchArray([
        'passes' => true,
        'finished' => true,
        'next_step' => 'box',
        'values' => ['order' => 'WO-1', 'parts' => ['P1', 'P2', 'P3', 'P4']],
    ]);
});

it('uses the config finish code and rejects finishing an empty step', function (): void {
    config()->set('qr-code.sequence.finish_code', 'END');
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat());
    $test = Livewire::test(SequenceWedgeTestComponent::class);

    expect(wedgeResult(wedgeScan($test, 'parts', 'END')))->toMatchArray([
        'passes' => false,
        'message' => __('filament-qr-code::ui.repeat_needs_codes', ['count' => 1]),
    ]);

    wedgeScan($test, 'parts', 'P1');
    expect(wedgeResult(wedgeScan($test, 'parts', 'END')))->toMatchArray(['passes' => true, 'next_step' => 'box']);
});

it('finishes a waiting step from the Done control and completes the sequence', function (): void {
    config()->set('qr-code.sequence.repeat_complete', 'wait');
    SequenceWedgeTestComponent::$steps = fn (): array => [
        ScanStep::make('order'),
        ScanStep::make('parts')->repeat(2),
    ];
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->autoSubmit();
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'order', 'WO-1');
    wedgeScan($test, 'parts', 'P1');

    $test->call('callSchemaComponentMethod', 'form.receipt', 'finishStep', ['step' => 'parts']);
    expect(wedgeResult($test))->toMatchArray(['passes' => false, 'message' => __('filament-qr-code::ui.repeat_needs_codes', ['count' => 2])]);

    expect(wedgeResult(wedgeScan($test, 'parts', 'P2')))->toMatchArray(['completed' => false, 'next_step' => 'parts']);
    $test->assertSet('submissions', 0);

    $test->call('callSchemaComponentMethod', 'form.receipt', 'finishStep', ['step' => 'parts']);
    expect(wedgeResult($test))->toMatchArray(['passes' => true, 'completed' => true, 'submitted' => true]);
    $test->assertSet('submissions', 1);
});

it('treats repeat steps as server work and checks every code on submit', function (): void {
    expect(QrScanSequence::make()->steps([ScanStep::make('parts')->repeat(2)])->hasServerSequenceWork())->toBeTrue();

    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(2));
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence;

    Livewire::test(SequenceWedgeTestComponent::class)
        ->set('data.receipt', ['order' => 'WO-1', 'parts' => ['P1', 'bad'], 'box' => 'B1'])
        ->call('validateForm')
        ->assertHasErrors(['data.receipt.parts']);
});

it('describes sticky and repeat steps in the payload', function (): void {
    $sequence = QrScanSequence::make()->statePath('receipt')->steps([
        ScanStep::make('po')->sticky(),
        ScanStep::make('parts')->repeat(3),
        ScanStep::make('extras')->repeat(),
        ScanStep::make('note')->optional(),
    ]);
    $sequence->container(Livewire::test(SequenceWedgeTestComponent::class)->instance()->form);

    expect($sequence->getComponentScanPayload()['steps'])->toBe([
        ['key' => 'po', 'optional' => false, 'sticky' => true, 'repeating' => false, 'repeat' => null, 'active' => true],
        ['key' => 'parts', 'optional' => false, 'sticky' => false, 'repeating' => true, 'repeat' => 3, 'active' => true],
        ['key' => 'extras', 'optional' => false, 'sticky' => false, 'repeating' => true, 'repeat' => null, 'active' => true],
        ['key' => 'note', 'optional' => true, 'sticky' => false, 'repeating' => false, 'repeat' => null, 'active' => true],
    ]);
});

it('falls back to an open-ended repeat when the count closure fails and warns in local dev', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
    Log::spy();
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(fn (array $values): int => $values['order'] === 'WO-2' ? 2 : 5));
    $test = Livewire::test(SequenceWedgeTestComponent::class);

    expect(wedgeResult(wedgeScan($test, 'parts', 'P1')))->toMatchArray(['passes' => true, 'next_step' => 'parts']);
    wedgeScan($test, 'order', 'WO-2');
    expect(wedgeResult(wedgeScan($test, 'parts', 'P2')))->toMatchArray(['next_step' => 'box']);

    Log::shouldHaveReceived('warning')->withArgs(fn (string $message): bool => str_contains($message, 'repeat'));
});

it('removes one collected code from a repeat step', function (): void {
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(3));
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'parts', 'P1');
    wedgeScan($test, 'parts', 'P2');

    $test->call('callSchemaComponentMethod', 'form.receipt', 'removeStepCode', ['step' => 'parts', 'index' => 0]);

    expect(wedgeResult($test))->toMatchArray(['passes' => true, 'next_step' => 'parts', 'values' => ['parts' => ['P2']]]);
    $test->assertSet('data.receipt.parts', ['P2']);
    expect(wedgeResult(wedgeScan($test, 'parts', 'P1')))->toMatchArray(['passes' => true]);
});

it('clears a sticky step and asks for it again', function (): void {
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->continuous();
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'po', 'PO-1');
    wedgeScan($test, 'sku', 'SKU-1');
    wedgeScan($test, 'lot', 'L001');

    $test->call('callSchemaComponentMethod', 'form.receipt', 'clearStep', ['step' => 'po']);

    expect(wedgeResult($test))->toMatchArray(['passes' => true, 'next_step' => 'po', 'values' => []]);
    $test->assertSet('data.receipt', []);
});

it('lets a step finish code override the config one', function (): void {
    config()->set('qr-code.sequence.finish_code', 'END');
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat()->finishWith('DONE'));
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'parts', 'P1');

    expect(wedgeResult(wedgeScan($test, 'parts', 'END')))->toMatchArray(['passes' => false])
        ->and(wedgeResult(wedgeScan($test, 'parts', 'DONE')))->toMatchArray(['finished' => true]);
});

it('whole-set check runs on early finish of repeat(n)', function (): void {
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(3)->finishWith('DONE'));
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence
        ->validateAllUsing(fn (array $values): ?array => count($values['parts'] ?? []) < 3 ? ['parts' => 'BOM short: missing P3.'] : null);
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'order', 'WO-1');
    wedgeScan($test, 'parts', 'P1');
    wedgeScan($test, 'parts', 'P2');

    $test->call('callSchemaComponentMethod', 'form.receipt', 'finishStep', ['step' => 'parts']);
    expect(wedgeResult($test))->toMatchArray(['passes' => false, 'message' => 'BOM short: missing P3.', 'invalid_step' => 'parts', 'next_step' => 'parts']);

    expect(wedgeResult(wedgeScan($test, 'parts', 'DONE')))->toMatchArray(['passes' => false, 'message' => 'BOM short: missing P3.', 'invalid_step' => 'parts']);
    $test->assertSet('data.receipt.parts', ['P1', 'P2']);
});

it('keeps the repeat count message on early finish when the whole set passes', function (): void {
    SequenceWedgeTestComponent::$steps = bomSteps(fn (ScanStep $step) => $step->repeat(3));
    SequenceWedgeTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->validateAllUsing(fn (): ?string => null);
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'order', 'WO-1');
    wedgeScan($test, 'parts', 'P1');

    $test->call('callSchemaComponentMethod', 'form.receipt', 'finishStep', ['step' => 'parts']);
    expect(wedgeResult($test))->toMatchArray(['passes' => false, 'message' => __('filament-qr-code::ui.repeat_needs_codes', ['count' => 3])]);
});

it('stage sees the same codes live and on submit', function (): void {
    $stage = new class implements ScanCheckStage
    {
        /** @var array<string, list<array<string, mixed>>> */
        public array $seen = ['live' => [], 'submit' => []];

        public string $phase = 'live';

        public function check(ScanCheckContext $context): bool|string|null
        {
            $this->seen[$this->phase][] = [$context->code, $context->values];

            return null;
        }
    };

    SequenceWedgeTestComponent::$steps = fn (): array => [
        ScanStep::make('order'),
        ScanStep::make('parts')->repeat(2)->pipe($stage),
        ScanStep::make('box')->pipe($stage),
    ];
    $test = Livewire::test(SequenceWedgeTestComponent::class);
    wedgeScan($test, 'order', 'WO-1');
    wedgeScan($test, 'parts', 'P1');
    wedgeScan($test, 'parts', 'P2');
    wedgeScan($test, 'box', 'B1');

    $stage->phase = 'submit';
    $test->call('validateForm')->assertHasNoErrors();

    expect($stage->seen['live'])->toBe([
        ['P1', ['order' => 'WO-1']],
        ['P2', ['order' => 'WO-1', 'parts' => ['P1']]],
        ['B1', ['order' => 'WO-1', 'parts' => ['P1', 'P2']]],
    ])->and($stage->seen['submit'])->toBe($stage->seen['live']);
});

it('routes scans through the server when a step normalizer is set so the step list shows the normalized code', function (): void {
    $upper = fn (string $rawValue): string => strtoupper($rawValue);

    expect(QrScanSequence::make()->steps([ScanStep::make('po')])->normalizeStepUsing($upper)->hasServerSequenceWork())->toBeTrue()
        ->and(QrScanSequence::make()->steps([ScanStep::make('po')->normalizeUsing($upper)])->hasServerSequenceWork())->toBeTrue()
        ->and(QrScanSequence::make([['key' => 'po', 'label' => 'PO']])->normalizeStepUsing($upper)->hasServerSequenceWork())->toBeTrue();
});
