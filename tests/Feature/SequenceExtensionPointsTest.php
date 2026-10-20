<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Events\ScanSequenceCompleted;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use RuntimeException;

class SequenceExtensionTestComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static ?Closure $steps = null;

    public static ?Closure $configure = null;

    public function mount(): void
    {
        $this->form->fill(['mode' => 'inbound']);
    }

    public function form(Schema $form): Schema
    {
        $steps = self::$steps instanceof Closure ? (self::$steps)() : [ScanStep::make('sku'), ScanStep::make('box')];
        $sequence = QrScanSequence::make()->statePath('receipt')->steps($steps);

        if (self::$configure instanceof Closure) {
            $sequence = (self::$configure)($sequence);
        }

        return $form->statePath('data')->components([TextInput::make('mode'), $sequence]);
    }

    public function sequence(): QrScanSequence
    {
        /** @var QrScanSequence */
        return $this->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}

function extStep(mixed $test, string $step, string $code, string $source = 'hardware'): array
{
    $test->call('callSchemaComponentMethod', 'form.receipt', 'handleStepScan', ['step' => $step, 'code' => $code, 'source' => $source]);

    return $test->effects['returns'][0];
}

beforeEach(function (): void {
    SequenceExtensionTestComponent::$steps = null;
    SequenceExtensionTestComponent::$configure = null;
});

it('step closure receives $get', function (): void {
    SequenceExtensionTestComponent::$steps = fn (): array => [
        ScanStep::make('sku')->label(fn (Get $get): string => 'SKU ('.$get('mode').')'),
    ];

    $test = Livewire::test(SequenceExtensionTestComponent::class);

    expect($test->instance()->sequence()->getScanFields())->toBe([['key' => 'sku', 'label' => 'SKU (inbound)']]);
});

it('step closures receive $values and $step without recursing', function (): void {
    SequenceExtensionTestComponent::$steps = fn (): array => [
        ScanStep::make('sku'),
        ScanStep::make('box')
            ->label(fn (array $values, ScanStep $step): string => "{$step->getKey()} for ".($values['sku'] ?? '?'))
            ->optional(fn (array $values): bool => ($values['sku'] ?? '') === 'LOOSE'),
    ];

    $test = Livewire::test(SequenceExtensionTestComponent::class);
    extStep($test, 'sku', 'LOOSE');

    $sequence = $test->instance()->sequence();

    expect($sequence->getScanFields()[1]['label'])->toBe('box for LOOSE')
        ->and($sequence->getScanSteps()[1]->isOptional())->toBeTrue()
        ->and($sequence->getSequenceState())->toBe(['sku' => 'LOOSE']);
});

it('resolves a step record with lookupUsing and runs step stages with the step and values', function (): void {
    $stage = new class implements ScanCheckStage
    {
        /** @var list<ScanCheckContext> */
        public array $seen = [];

        public function check(ScanCheckContext $context): bool|string|null
        {
            $this->seen[] = $context;

            return $context->record['open'] ? null : 'Box is closed.';
        }
    };

    SequenceExtensionTestComponent::$steps = fn (): array => [
        ScanStep::make('sku'),
        ScanStep::make('box')
            ->lookupUsing(fn (string $code, array $values): array => ['id' => $code, 'open' => $code !== 'BOX-CLOSED', 'sku' => $values['sku'] ?? null])
            ->pipe($stage),
    ];

    $test = Livewire::test(SequenceExtensionTestComponent::class);
    extStep($test, 'sku', 'SKU-1');

    expect(extStep($test, 'box', 'BOX-CLOSED'))->toMatchArray(['passes' => false, 'message' => 'Box is closed.'])
        ->and($stage->seen[0]->step?->getKey())->toBe('box')
        ->and($stage->seen[0]->values)->toBe(['sku' => 'SKU-1'])
        ->and($stage->seen[0]->record['sku'])->toBe('SKU-1')
        ->and(extStep($test, 'box', 'BOX-1'))->toMatchArray(['passes' => true, 'record_key' => 'BOX-1']);
});

it('normalizes step codes with presets, then the step normalizer', function (): void {
    SequenceExtensionTestComponent::$steps = fn (): array => [
        ScanStep::make('sku')->normalizeUsing(fn (string $code): string => str_replace(' ', '-', $code))->validateUsing(['starts_with:SKU-']),
    ];
    SequenceExtensionTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence->normalizeWith(uppercase: true, stripPrefix: ']C1');

    $test = Livewire::test(SequenceExtensionTestComponent::class);

    expect(extStep($test, 'sku', ' ]C1sku 1 '))->toMatchArray(['passes' => true, 'code' => 'SKU-1'])
        ->and($test->instance()->sequence()->getScanPayload()['normalize'])->toBe(['trim' => true, 'uppercase' => true, 'stripPrefixes' => [']C1']]);
});

it('runs onStepAccepted and onStepRejected as side effects only', function (): void {
    $log = [];
    SequenceExtensionTestComponent::$steps = fn (): array => [ScanStep::make('sku')->validateUsing(['starts_with:SKU-']), ScanStep::make('box')];
    SequenceExtensionTestComponent::$configure = function (QrScanSequence $sequence) use (&$log): QrScanSequence {
        return $sequence
            ->onStepAccepted(function (string $step, ScanResult $result, array $values) use (&$log): string {
                $log[] = ['accepted', $step, $result->code, $values];

                return 'ignored';
            })
            ->onStepRejected(function (string $step, ScanResult $result) use (&$log): bool {
                $log[] = ['rejected', $step, $result->message];

                return true;
            });
    };

    $test = Livewire::test(SequenceExtensionTestComponent::class);
    $rejected = extStep($test, 'sku', 'BAD');
    $accepted = extStep($test, 'sku', 'SKU-1');

    expect($rejected['passes'])->toBeFalse()
        ->and($accepted)->toMatchArray(['passes' => true, 'next_step' => 'box'])
        ->and($log[0][0])->toBe('rejected')
        ->and($log[0][2])->toBe($rejected['message'])
        ->and($log[1])->toBe(['accepted', 'sku', 'SKU-1', ['sku' => 'SKU-1']]);
});

it('reports a throwing step hook without changing the result', function (): void {
    Exceptions::fake();
    SequenceExtensionTestComponent::$configure = fn (QrScanSequence $sequence) => $sequence
        ->onStepAccepted(fn () => throw new RuntimeException('notify failed'));

    $test = Livewire::test(SequenceExtensionTestComponent::class);

    expect(extStep($test, 'sku', 'SKU-1'))->toMatchArray(['passes' => true, 'next_step' => 'box']);
    $test->assertSet('data.receipt.sku', 'SKU-1');
    Exceptions::assertReported(RuntimeException::class);
});

it('puts the sequence, values and Livewire component on step events', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, ScanSequenceCompleted::class]);
    SequenceExtensionTestComponent::$steps = fn (): array => [ScanStep::make('sku')->validateUsing(['starts_with:SKU-']), ScanStep::make('box')];

    $test = Livewire::test(SequenceExtensionTestComponent::class);
    extStep($test, 'sku', 'BAD');
    extStep($test, 'sku', 'SKU-1');
    extStep($test, 'box', 'BOX-1');

    Event::assertDispatched(ScanRejected::class, fn (ScanRejected $event): bool => $event->field === 'sku'
        && $event->statePath === 'data.receipt.sku'
        && $event->sequence === 'data.receipt'
        && $event->livewireClass === SequenceExtensionTestComponent::class);
    Event::assertDispatched(ScanAccepted::class, fn (ScanAccepted $event): bool => $event->field === 'box'
        && $event->values === ['sku' => 'SKU-1', 'box' => 'BOX-1']
        && $event->livewireId === $test->id());
    Event::assertDispatched(ScanSequenceCompleted::class, fn (ScanSequenceCompleted $event): bool => $event->statePath === 'data.receipt'
        && $event->livewireClass === SequenceExtensionTestComponent::class
        && $event->livewireId === $test->id());
});

it('makes ScanStep macroable', function (): void {
    ScanStep::macro('serial', fn () => $this->validateUsing(['starts_with:SN-'])->repeat());

    expect(ScanStep::make('serials')->serial()->isRepeating())->toBeTrue();
});
