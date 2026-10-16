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
