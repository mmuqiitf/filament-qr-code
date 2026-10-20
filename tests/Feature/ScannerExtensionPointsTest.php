<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Mmuqiitf\FilamentQrCode\Contracts\ScanCheckStage;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;
use Mmuqiitf\FilamentQrCode\Events\ScanAccepted;
use Mmuqiitf\FilamentQrCode\Events\ScanRejected;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckContext;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\RejectOutOfStockStage;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(function (): void {
    ScanCheckProduct::createTableWithProducts();
    RejectOutOfStockStage::$seen = [];
});

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);

function extScan(Closure $configure, string $code, string $source = 'hardware'): array
{
    $test = ScanChecksTestComponent::test($configure);
    ScanChecksTestComponent::scan($test, $code, $source);

    return ['test' => $test, 'result' => $test->effects['returns'][0]];
}

it('lookupUsing resolves from a closure and feeds onScanned', function (): void {
    $received = null;

    ['result' => $result] = extScan(function (QrScanner $scanner) use (&$received): QrScanner {
        return $scanner
            ->lookupUsing(fn (string $code): ?array => $code === 'API-1' ? ['id' => 77, 'name' => 'From API'] : null)
            ->onScanned(function (mixed $record) use (&$received): void {
                $received = $record;
            });
    }, 'API-1');

    expect($result)->toMatchArray(['passes' => true, 'record_key' => 77])
        ->and($received)->toBe(['id' => 77, 'name' => 'From API']);
});

it('rejects with record_not_found when the resolver finds nothing', function (): void {
    ['result' => $result] = extScan(fn (QrScanner $scanner) => $scanner->lookupUsing(fn (): bool => false), 'NOPE');

    expect($result)->toMatchArray(['passes' => false, 'message' => __('filament-qr-code::ui.record_not_found', ['code' => 'NOPE'])]);
});

it('replaces the model lookup by default', function (): void {
    ['result' => $result] = extScan(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->lookupUsing(fn (string $code): array => ['id' => 'resolver']), 'SKU-1');

    expect($result['record_key'])->toBe('resolver');
});

it('runs a fallback resolver only after the model lookup misses', function (): void {
    $calls = 0;
    $configure = function (QrScanner $scanner) use (&$calls): QrScanner {
        return $scanner
            ->lookup(ScanCheckProduct::class, 'sku')
            ->lookupUsing(function (string $code) use (&$calls): object {
                $calls++;

                return (object) ['key' => "legacy-{$code}"];
            }, fallback: true);
    };

    $productId = ScanCheckProduct::query()->where('sku', 'SKU-1')->value('id');

    expect(extScan($configure, 'SKU-1')['result']['record_key'])->toBe($productId)
        ->and($calls)->toBe(0)
        ->and(extScan($configure, 'OLD-9')['result']['record_key'])->toBe('legacy-OLD-9')
        ->and($calls)->toBe(1);
});

it('evaluates lookup model and column closures', function (): void {
    ['result' => $result] = extScan(fn (QrScanner $scanner) => $scanner
        ->lookup(fn (): string => ScanCheckProduct::class, fn (): string => 'sku'), 'SKU-1');

    expect($result['passes'])->toBeTrue();
});

it('normalizes on the server before rules and lookup', function (): void {
    ['result' => $result, 'test' => $test] = extScan(fn (QrScanner $scanner) => $scanner
        ->normalizeUsing(fn (string $code): string => strtoupper(str_replace(' ', '-', $code)))
        ->validateUsing(['starts_with:SKU-'])
        ->lookup(ScanCheckProduct::class, 'sku'), 'sku 1');

    expect($result)->toMatchArray(['passes' => true, 'code' => 'SKU-1']);
    $test->assertSet('data.sku', 'SKU-1');
});

it('applies the normalize presets on the server and sends them to the browser', function (): void {
    $configure = fn (QrScanner $scanner) => $scanner
        ->normalizeWith(uppercase: true, stripPrefix: [']C1', ']Q1'])
        ->validateUsing(['starts_with:SKU-']);

    expect(extScan($configure, ' ]C1sku-1 ')['result'])->toMatchArray(['passes' => true, 'code' => 'SKU-1'])
        ->and(QrScanner::make('a')->normalizeWith(uppercase: true, stripPrefix: ']C1')->getScanPayload()['normalize'])
        ->toBe(['trim' => true, 'uppercase' => true, 'stripPrefixes' => [']C1']])
        ->and(QrScanner::make('a')->getScanPayload())->not->toHaveKey('normalize');
});

it('runs piped stages after the built-in checks, resolved from the container', function (): void {
    ScanCheckProduct::query()->where('sku', 'SKU-1')->update(['stock' => 0]);
    $order = [];

    ['result' => $result] = extScan(function (QrScanner $scanner) use (&$order): QrScanner {
        return $scanner
            ->lookup(ScanCheckProduct::class, 'sku')
            ->validateUsing(function () use (&$order): void {
                $order[] = 'closure';
            })
            ->pipe(RejectOutOfStockStage::class);
    }, 'SKU-1');

    expect($result)->toMatchArray(['passes' => false, 'message' => 'No stock left.'])
        ->and($order)->toBe(['closure'])
        ->and(RejectOutOfStockStage::$seen)->toHaveCount(1)
        ->and(RejectOutOfStockStage::$seen[0])->toBeInstanceOf(ScanCheckContext::class)
        ->and(RejectOutOfStockStage::$seen[0]->code)->toBe('SKU-1')
        ->and(RejectOutOfStockStage::$seen[0]->component)->toBeInstanceOf(QrScanner::class);
});

it('stops at the first failing stage and accepts stage instances', function (): void {
    $second = new class implements ScanCheckStage
    {
        public bool $ran = false;

        public function check(ScanCheckContext $context): bool|string|null
        {
            $this->ran = true;

            return null;
        }
    };
    $first = new class implements ScanCheckStage
    {
        public function check(ScanCheckContext $context): bool|string|null
        {
            return false;
        }
    };

    ['result' => $result] = extScan(fn (QrScanner $scanner) => $scanner->pipe($first, $second), 'X-1');

    expect($result)->toMatchArray(['passes' => false, 'message' => __('filament-qr-code::ui.scan_rejected')])
        ->and($second->ran)->toBeFalse();
});

it('turns a throwing stage into scan_failed', function (): void {
    $stage = new class implements ScanCheckStage
    {
        public function check(ScanCheckContext $context): bool|string|null
        {
            throw new RuntimeException('down');
        }
    };

    expect(extScan(fn (QrScanner $scanner) => $scanner->pipe($stage), 'X-1')['result'])
        ->toMatchArray(['passes' => false, 'message' => __('filament-qr-code::ui.scan_failed')]);
});

it('re-runs piped stages on form submit', function (): void {
    ScanCheckProduct::query()->where('sku', 'SKU-1')->update(['stock' => 0]);

    ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->pipe(RejectOutOfStockStage::class))
        ->set('data.sku', 'SKU-1')
        ->call('submit')
        ->assertHasErrors(['data.sku']);
});

it('refuses a stage class that does not implement the contract', function (): void {
    QrScanner::make('sku')->pipe(stdClass::class);
})->throws(InvalidArgumentException::class);

it('puts the state path and Livewire component on scan events', function (): void {
    Event::fake([ScanAccepted::class, ScanRejected::class, QrCodeScanned::class]);

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(['starts_with:SKU-']));
    ScanChecksTestComponent::scan($test, 'SKU-1');
    ScanChecksTestComponent::scan($test, 'BAD');

    $matches = fn (ScanAccepted|ScanRejected $event): bool => $event->statePath === 'data.sku'
        && $event->livewireClass === ScanChecksTestComponent::class
        && $event->livewireId === $test->id();

    Event::assertDispatched(ScanAccepted::class, $matches);
    Event::assertDispatched(ScanRejected::class, $matches);
    Event::assertDispatched(QrCodeScanned::class, fn (QrCodeScanned $event): bool => ($event->context['state_path'] ?? null) === 'data.sku');
});

it('makes the scan components macroable', function (): void {
    QrScanner::macro('forWarehouse', fn () => $this->normalizeWith(uppercase: true));

    expect(QrScanner::make('a')->forWarehouse()->getScanPayload()['normalize']['uppercase'])->toBeTrue();
});
