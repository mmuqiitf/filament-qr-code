<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\AiRule;
use Mmuqiitf\FilamentQrCode\Gs1\CheckDigit;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;
use Mmuqiitf\FilamentQrCode\Gs1\SyntaxTable;

/**
 * @return array{release: string, cases: list<array{name: string, elements: array<string, string>, bracketed: string, raw: string}>}
 */
function gs1Fixtures(): array
{
    $decoded = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/gs1/element-strings.json'), true);

    if (! is_array($decoded)) {
        throw new RuntimeException('GS1 fixtures did not decode.');
    }

    /** @var array{release: string, cases: list<array{name: string, elements: array<string, string>, bracketed: string, raw: string}>} $decoded */
    return $decoded;
}

it('parses and builds the element-string fixtures', function (array $case): void {
    $built = Gs1::build($case['elements']);

    expect($built->raw())->toBe($case['raw'])
        ->and($built->bracketed())->toBe($case['bracketed'])
        ->and($built->toArray())->toBe($case['elements'])
        ->and(Gs1::parse($case['raw'])->toArray())->toBe($case['elements'])
        ->and(Gs1::parse($case['bracketed'])->toArray())->toBe($case['elements'])
        ->and(Gs1::parse($case['raw'])->raw())->toBe($case['raw'])
        ->and(Gs1::parse(']C1'.$case['raw'])->get('01') ?? Gs1::parse(']C1'.$case['raw'])->get('00') ?? Gs1::parse(']C1'.$case['raw'])->get('8003'))
        ->not->toBeNull();
})->with(function (): array {
    $datasets = [];

    foreach (gs1Fixtures()['cases'] as $case) {
        $datasets[$case['name']] = [$case];
    }

    return $datasets;
});

it('reads a symbology identifier and ignores a group separator after a fixed field', function (): void {
    $raw = '0109521101530001'.Gs1::SEPARATOR.'17251231';

    $parsed = Gs1::parse(']d2'.$raw);

    expect($parsed->toArray())->toBe([
        '01' => '09521101530001',
        '17' => '251231',
    ])->and($parsed->raw())->toBe('010952110153000117251231')
        ->and($parsed->elements()[0]->title)->toBe('GTIN');
});

it('ignores a trailing group separator', function (): void {
    expect(Gs1::parse('10LOT-1'.Gs1::SEPARATOR)->toArray())->toBe(['10' => 'LOT-1']);
});

it('round-trips every application identifier in the syntax dictionary', function (): void {
    expect(SyntaxTable::rules())->not->toBeEmpty();

    foreach (SyntaxTable::rules() as $rule) {
        $minimal = gs1Sample($rule, false);
        $parsed = Gs1::parse(Gs1::build([$rule->ai => $minimal])->bracketed());

        expect($parsed->toArray())->toBe([$rule->ai => $minimal])
            ->and($parsed->elements()[0]->title)->toBe($rule->title);

        $full = gs1Sample($rule, true);

        expect(Gs1::parse(Gs1::build([$rule->ai => $full])->raw())->get($rule->ai))->toBe($full);
    }
});

it('ships the pinned syntax dictionary', function (): void {
    $dictionary = (string) file_get_contents(dirname(__DIR__, 2).'/resources/gs1/gs1-syntax-dictionary.txt');

    expect($dictionary)->toContain('# Release: '.gs1Fixtures()['release'])
        ->and(SyntaxTable::rules()['01']->fixed)->toBeTrue()
        ->and(SyntaxTable::rules()['01']->maxLength)->toBe(14)
        ->and(SyntaxTable::rules()['10']->fixed)->toBeFalse()
        ->and(SyntaxTable::rules()['3100']->title)->toBe(SyntaxTable::rules()['3105']->title);
});

it('rejects element strings that break the syntax dictionary', function (string $input, string $reason): void {
    expect(fn () => Gs1::parse($input))->toThrow(InvalidGs1Exception::class, $reason);
})->with([
    'empty' => ['', 'the element string is empty'],
    'unknown ai' => ['(05)1234', 'unknown application identifier 05'],
    'short gtin' => ['(01)0952110153000', 'must be 14 characters, got 13'],
    'letter in a numeric ai' => ['(01)0952110153000A', 'outside digits'],
    'duplicate' => ['(01)09521101530001(01)09521101530001', 'duplicate application identifier 01'],
    'cpid lowercase' => ['(8010)abc', 'character set 39'],
    'bad signature character' => ['(8030)abc+', 'character set 64'],
    'unseparated variable overflow' => ['10'.str_repeat('A', 20).'17251231', 'must be 1 to 20 characters'],
    'missing closing parenthesis' => ['(01', 'missing )'],
]);

it('rejects a build with no elements or a list', function (): void {
    expect(fn () => Gs1::build([]))->toThrow(InvalidGs1Exception::class, 'empty')
        ->and(fn () => Gs1::build(['09521101530001']))->toThrow(InvalidGs1Exception::class, 'string keys');
});

it('keeps an application identifier that PHP stores as an integer key', function (): void {
    expect(Gs1::build(['10' => 'LOT-1', '8003' => '09521101530001'])->toArray())
        ->toBe(['10' => 'LOT-1', '8003' => '09521101530001']);
});

function gs1Sample(AiRule $rule, bool $fillOptional): string
{
    $value = '';

    foreach ($rule->components as $component) {
        if ($component['optional'] && ! $fillOptional) {
            continue;
        }

        $length = $fillOptional ? $component['max'] : $component['min'];
        $linters = $component['linters'];

        $value .= match (true) {
            in_array('csum', $linters, true) => ($body = str_repeat('1', $length - 1)).CheckDigit::calculate($body),
            in_array('yyyymmdd', $linters, true) => '20261231',
            in_array('yymmd0', $linters, true), in_array('yymmdd', $linters, true) => '261231',
            $component['kind'] === 'N' => str_repeat('1', $length),
            default => str_repeat('A', $length),
        };
    }

    return $value;
}
