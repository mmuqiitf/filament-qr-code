<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

beforeEach(function (): void {
    CarbonImmutable::setTestNow('2026-11-09 12:00:00');
});

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('rejects a wrong check digit when parsing and building', function (string $ai, string $value): void {
    expect(fn () => Gs1::parse("({$ai}){$value}"))
        ->toThrow(InvalidGs1Exception::class, "AI {$ai}")
        ->and(fn () => Gs1::parse("({$ai}){$value}"))
        ->toThrow(InvalidGs1Exception::class, 'check digit')
        ->and(fn () => Gs1::build([$ai => $value]))
        ->toThrow(InvalidGs1Exception::class, 'check digit');
})->with([
    'sscc' => ['00', '000952110153000017'],
    'gtin' => ['01', '09521101530002'],
    'content gtin' => ['02', '09521101530009'],
    'mto gtin' => ['03', '09521101530003'],
    'gln ship to' => ['410', '1234567890129'],
    'gln' => ['414', '1234567890120'],
    'gln party' => ['417', '1234567890125'],
    'gdti with serial' => ['253', '1234567890129SER'],
    'grai with serial' => ['8003', '09521101530002SER-1'],
    'gsrn' => ['8018', '123456789012345670'],
    'itip' => ['8006', '095211015300020102'],
]);

it('names the expected check digit', function (): void {
    expect(fn () => Gs1::parse('(01)09521101530002'))
        ->toThrow(InvalidGs1Exception::class, 'AI 01 (GTIN) has an invalid check digit, expected 1');
});

it('accepts a correct check digit', function (string $ai, string $value): void {
    expect(Gs1::parse("({$ai}){$value}")->get($ai))->toBe($value)
        ->and(Gs1::build([$ai => $value])->get($ai))->toBe($value);
})->with([
    'sscc' => ['00', '000952110153000018'],
    'gtin' => ['01', '09521101530001'],
    'gln' => ['414', '1234567890128'],
    'gdti with serial' => ['253', '1234567890128SER'],
    'grai with serial' => ['8003', '09521101530001SER-1'],
    'itip' => ['8006', '095211015300010102'],
]);

it('rejects a date that is not on the calendar', function (string $ai, string $value, string $reason): void {
    expect(fn () => Gs1::parse("(01)09521101530001({$ai}){$value}"))
        ->toThrow(InvalidGs1Exception::class, $reason)
        ->and(fn () => Gs1::build(['01' => '09521101530001', $ai => $value]))
        ->toThrow(InvalidGs1Exception::class, $reason);
})->with([
    'month 13' => ['17', '261301', 'AI 17 (USE BY or EXPIRY) is not a valid date'],
    'day 99' => ['17', '261299', 'not a valid date'],
    'month 00' => ['11', '260001', 'not a valid date'],
    '30 february' => ['15', '260230', 'not a valid date'],
    '29 february in a common year' => ['13', '270229', 'not a valid date'],
    'day 00 where the day is required' => ['7006', '260100', 'not a valid date'],
    'eight-digit date' => ['7250', '20260230', 'not a valid date'],
    'second harvest date' => ['7007', '260101261301', 'not a valid date'],
    'date before a time' => ['7003', '2613011200', 'not a valid date'],
]);

it('accepts calendar dates and day 00 where the dictionary allows it', function (string $ai, string $value): void {
    expect(Gs1::parse("(01)09521101530001({$ai}){$value}")->get($ai))->toBe($value);
})->with([
    'expiry' => ['17', '261231'],
    'expiry day 00' => ['17', '260200'],
    'leap day' => ['11', '280229'],
    'harvest range' => ['7007', '260101260131'],
    'eight-digit date' => ['7250', '19800229'],
    'date with time' => ['7003', '2612311430'],
]);

it('returns a date as CarbonImmutable and keeps the value a string', function (): void {
    $gs1 = Gs1::parse('(01)09521101530001(17)261231(10)LOT-1');

    expect($gs1->get('17'))->toBe('261231')
        ->and($gs1->date('17'))->toBeInstanceOf(CarbonImmutable::class)
        ->and($gs1->date('17')?->toDateString())->toBe('2026-12-31')
        ->and($gs1->date('17')?->toTimeString())->toBe('00:00:00')
        ->and($gs1->elements()[1]->date()?->toDateString())->toBe('2026-12-31')
        ->and($gs1->date('10'))->toBeNull()
        ->and($gs1->date('11'))->toBeNull()
        ->and($gs1->elements()[0]->date())->toBeNull();
});

it('reads day 00 as the last day of the month', function (string $value, string $date): void {
    expect(Gs1::parse("(17){$value}")->date('17')?->toDateString())->toBe($date);
})->with([
    'february, common year' => ['260200', '2026-02-28'],
    'february, leap year' => ['280200', '2028-02-29'],
    'april' => ['260400', '2026-04-30'],
    'december' => ['261200', '2026-12-31'],
]);

it('reads an eight-digit date and the first date of a range', function (): void {
    expect(Gs1::parse('(8018)123456789012345675(7250)19800229')->date('7250')?->toDateString())->toBe('1980-02-29')
        ->and(Gs1::parse('(01)09521101530001(7007)260101260131')->date('7007')?->toDateString())->toBe('2026-01-01')
        ->and(Gs1::parse('(01)09521101530001(7003)2612311430')->date('7003')?->toDateString())->toBe('2026-12-31');
});

it('picks the century with the GS1 sliding window', function (string $now, string $value, string $date): void {
    CarbonImmutable::setTestNow($now);

    expect(Gs1::parse("(17){$value}")->date('17')?->toDateString())->toBe($date);
})->with([
    'this century' => ['2026-11-09', '261231', '2026-12-31'],
    '50 years ahead stays in this century' => ['2026-11-09', '760101', '2076-01-01'],
    '51 years ahead is the last century' => ['2026-11-09', '770101', '1977-01-01'],
    '49 years back stays in this century' => ['2080-06-01', '310101', '2031-01-01'],
    '50 years back is the next century' => ['2080-06-01', '300101', '2130-01-01'],
]);

it('checks 29 february against the century the window picks', function (): void {
    expect(Gs1::parse('(17)000229')->date('17')?->toDateString())->toBe('2000-02-29');

    CarbonImmutable::setTestNow('2060-01-01');

    expect(fn () => Gs1::parse('(17)000229'))->toThrow(InvalidGs1Exception::class, 'not a valid date');
});

it('returns a decimal field as an exact decimal string', function (string $ai, string $value, string $decimal): void {
    $gs1 = Gs1::parse("(01)09521101530001({$ai}){$value}");

    expect($gs1->get($ai))->toBe($value)
        ->and($gs1->decimal($ai))->toBe($decimal)
        ->and($gs1->elements()[1]->decimal())->toBe($decimal);
})->with([
    'net weight, 3 places' => ['3103', '001250', '1.250'],
    'net weight, no places' => ['3100', '001250', '1250'],
    'net weight, 5 places' => ['3105', '000125', '0.00125'],
    'zero with places' => ['3102', '000000', '0.00'],
    'gross weight lb' => ['3401', '123456', '12345.6'],
    'volume' => ['3695', '100000', '1.00000'],
    'amount' => ['3902', '12345', '123.45'],
    'amount, more places than digits' => ['3904', '5', '0.0005'],
    'amount with currency' => ['3912', '9781999', '19.99'],
    'price' => ['3929', '1', '0.000000001'],
    'price with currency' => ['3932', '360150000', '1500.00'],
    'percent off' => ['3941', '0250', '25.0'],
    'price per unit' => ['3952', '000199', '1.99'],
]);

it('returns null for a decimal on a field without implied decimals', function (): void {
    $gs1 = Gs1::parse('(01)09521101530001(30)12(10)LOT-1');

    expect($gs1->decimal('01'))->toBeNull()
        ->and($gs1->decimal('30'))->toBeNull()
        ->and($gs1->decimal('10'))->toBeNull()
        ->and($gs1->decimal('3103'))->toBeNull()
        ->and($gs1->elements()[0]->decimal())->toBeNull();
});
