<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Validator;
use Mmuqiitf\FilamentQrCode\Gs1\CheckDigit;
use Mmuqiitf\FilamentQrCode\Validation\ValidCheckDigit;

it('calculates the GS1 mod-10 check digit', function (string $body, int $digit): void {
    expect(CheckDigit::calculate($body))->toBe($digit)
        ->and(CheckDigit::validate($body.$digit))->toBeTrue()
        ->and(CheckDigit::validate($body.(($digit + 1) % 10)))->toBeFalse();
})->with([
    'ean-13' => ['400638133393', 1],
    'ean-8' => ['9638507', 4],
    'upc-a' => ['03600029145', 2],
    'itf-14' => ['0001234567890', 5],
    'gtin-14' => ['0952110153000', 1],
    'sscc' => ['00095211015300001', 8],
    'gln' => ['123456789012', 8],
]);

it('rejects a value that is not digits with a check digit', function (string $value): void {
    expect(CheckDigit::validate($value))->toBeFalse();
})->with([
    'empty' => '',
    'one digit' => '5',
    'letters' => '400638133393A',
]);

it('refuses to calculate a check digit for non-digits', function (): void {
    expect(fn () => CheckDigit::calculate(''))->toThrow(InvalidArgumentException::class)
        ->and(fn () => CheckDigit::calculate('12A'))->toThrow(InvalidArgumentException::class);
});

it('validates a field with ValidCheckDigit', function (): void {
    $rule = ['gtin' => [new ValidCheckDigit]];

    expect(Validator::make(['gtin' => '4006381333931'], $rule)->passes())->toBeTrue()
        ->and(Validator::make(['gtin' => '4006381333932'], $rule)->fails())->toBeTrue()
        ->and(Validator::make(['gtin' => '4006381333932'], $rule)->errors()->first('gtin'))
        ->toBe('The gtin must end with a valid GS1 check digit.');
});
