<?php

declare(strict_types=1);

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
    $startsWithSku = new class implements \Illuminate\Contracts\Validation\ValidationRule
    {
        public function validate(string $attribute, mixed $value, \Closure $fail): void
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
        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('active', true),
    ));

    ScanChecksTestComponent::scan($test, 'SKU-OLD')
        ->assertReturned(fn (array $result): bool => $result['passes'] === false);
});

it('runs rules before the lookup', function (): void {
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $test = ScanChecksTestComponent::test(fn (QrScanner $scanner) => $scanner
        ->lookup(ScanCheckProduct::class, 'sku')
        ->validateUsing(['max:3']));

    \Illuminate\Support\Facades\DB::flushQueryLog();

    ScanChecksTestComponent::scan($test, 'SKU-1')
        ->assertReturned(fn (array $result): bool => str_contains((string) $result['message'], 'must not be greater than 3'));

    expect(collect(\Illuminate\Support\Facades\DB::getQueryLog())->pluck('query')->filter(fn (string $sql): bool => str_contains($sql, 'scan_check_products')))->toBeEmpty();
});

it('reports server checks once a lookup is configured', function (): void {
    expect(QrScanner::make('sku')->lookup(ScanCheckProduct::class, 'sku')->hasServerScanChecks())->toBeTrue();
});
