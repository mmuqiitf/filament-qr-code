<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Validation\ValidationException;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanChecksTestComponent;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

afterEach(fn () => ScanChecksTestComponent::$configureScanner = null);

it('handleScan returns passes=false with translated message when rule fails', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(['string', 'max:5']));

    ScanChecksTestComponent::scan($test, 'TOO-LONG-CODE')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'The Sku field must not be greater than 5 characters.')
        ->assertSet('data.sku', null);
});

it('accepts a code that passes every rule', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(['string', 'max:10']));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true)
        ->assertSet('data.sku', 'SKU-1');
});

it('accepts a rule object and merges repeated validateUsing calls', function (): void {
    $startsWithSku = new class implements ValidationRule
    {
        public function validate(string $attribute, mixed $value, Closure $fail): void
        {
            if (! str_starts_with((string) $value, 'SKU-')) {
                $fail('Codes must start with SKU-.');
            }
        }
    };

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->validateUsing(['max:10'])
        ->validateUsing($startsWithSku));

    ScanChecksTestComponent::scan($test, 'ABC')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'Codes must start with SKU-.');

    ScanChecksTestComponent::scan($test, 'SKU-123456789')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'The Sku field must not be greater than 10 characters.');
});

it('reports server checks once a rule is configured', function (): void {
    expect(QrScanner::make('sku')->hasServerScanChecks())->toBeFalse()
        ->and(QrScanner::make('sku')->validateUsing(['max:5'])->hasServerScanChecks())->toBeTrue();
});
it('lookup sets record on ScanResult; missing SKU rejects with record_not_found', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true
            && $result['record_key'] === ScanCheckProduct::query()->where('sku', 'SKU-1')->value('id'));

    ScanChecksTestComponent::scan($test, 'SKU-404')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'No record found for "SKU-404".');
});

it('translates record_not_found', function (): void {
    app()->setLocale('id');
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(ScanCheckProduct::class, 'sku'));

    ScanChecksTestComponent::scan($test, 'SKU-404')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'Data untuk "SKU-404" tidak ditemukan.');
});

it('applies modifyQueryUsing to the lookup', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->lookup(
        ScanCheckProduct::class,
        'sku',
        modifyQueryUsing: fn (Builder $query) => $query->where('active', true),
    ));

    ScanChecksTestComponent::scan($test, 'SKU-OLD')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false);
});

it('runs rules before the lookup', function (): void {
    DB::enableQueryLog();

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->validateUsing(['max:3']));

    DB::flushQueryLog();

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => str_contains((string) $result['message'], 'must not be greater than 3'));

    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql): bool => str_contains($sql, 'scan_check_products')))->toBeEmpty();
});

it('reports server checks once a lookup is configured', function (): void {
    expect(QrScanner::make('sku')->lookup(ScanCheckProduct::class, 'sku')->hasServerScanChecks())->toBeTrue();
});
it('closure check receives the looked-up record and its message rejects the scan', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->validateUsing(fn (string $code, ?ScanCheckProduct $record): ?string => $record?->stock > 0 ? null : 'Out of stock'));

    ScanChecksTestComponent::scan($test, 'SKU-EMPTY')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false && $result['message'] === 'Out of stock');

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === true);
});

it('rejects with scan_rejected when a closure returns false', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(fn (): bool => false));

    ScanChecksTestComponent::scan($test, 'ANY')
        ->assertReturned(fn (array $result): bool => $result['message'] === __('filament-qr-code::ui.scan_rejected'));
});

it('passes the scan source to closures', function (): void {
    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->validateUsing(fn (?ScanInput $source): ?string => $source === ScanInput::Hardware ? null : 'Hardware only'));

    ScanChecksTestComponent::scan($test, 'X', 'camera')->assertReturned(fn (array $result): bool => $result['message'] === 'Hardware only');
    ScanChecksTestComponent::scan($test, 'X', 'hardware')->assertReturned(fn (array $result): bool => $result['passes'] === true);
});

it('runs rules, then lookup, then closures and stops at the first failure', function (): void {
    $calls = [];

    $test = ScanChecksTestComponent::test(function (QrScanner $scanner) use (&$calls) {
        return $scanner
            ->validateUsing(function () use (&$calls): ?string {
                $calls[] = 'closure-1';

                return 'First closure fails';
            })
            ->validateUsing(function () use (&$calls): ?string {
                $calls[] = 'closure-2';

                return null;
            })
            ->lookup(ScanCheckProduct::class, 'sku')
            ->validateUsing(['max:10']);
    });

    $calls = [];
    ScanChecksTestComponent::scan($test, 'SKU-404')->assertReturned(fn (array $result): bool => str_starts_with((string) $result['message'], 'No record found'));
    expect($calls)->toBe([]);

    $calls = [];
    ScanChecksTestComponent::scan($test, 'SKU-1')->assertReturned(fn (array $result): bool => $result['message'] === 'First closure fails');
    expect($calls)->toBe(['closure-1']);
});
it('a throwing check becomes scan_failed and is reported', function (): void {
    Exceptions::fake();

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(fn () => throw new RuntimeException('boom')));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false
            && $result['message'] === 'The scan could not be checked. Please try again.')
        ->assertSet('data.sku', null);

    Exceptions::assertReported(RuntimeException::class);
});

it('converts a ValidationException into its message without reporting it', function (): void {
    Exceptions::fake();

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner->validateUsing(
        fn () => throw ValidationException::withMessages(['code' => 'Blocked code']),
    ));

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => $result['message'] === 'Blocked code');

    Exceptions::assertNothingReported();
});
