<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

it('tells which AI failed and why', function (string $input, string $kind, ?string $ai): void {
    try {
        Gs1::parse($input);
    } catch (InvalidGs1Exception $exception) {
        expect($exception->kind)->toBe($kind)
            ->and($exception->ai)->toBe($ai);

        return;
    }

    test()->fail('Expected InvalidGs1Exception');
})->with([
    'empty' => ['', 'empty', null],
    'syntax' => ['(01', 'syntax', null],
    'unknown AI in brackets' => ['(23)X', 'unknown_ai', '23'],
    'unknown AI raw' => ['2399', 'unknown_ai', null],
    'duplicate' => ['(10)A(10)B', 'duplicate_ai', '10'],
    'length' => ['(01)123', 'length', '01'],
    'character set' => ['(01)0950600013435A', 'character_set', '01'],
    'check digit' => ['(01)09506000134353', 'check_digit', '01'],
    'date' => ['(17)261340', 'date', '17'],
]);

it('keeps the existing constructor working', function (): void {
    $exception = new InvalidGs1Exception('x', 'broken');

    expect($exception->kind)->toBe('syntax')
        ->and($exception->ai)->toBeNull()
        ->and($exception->getMessage())->toBe('Invalid GS1 element string [x]: broken.');
});
