<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Exceptions\InvalidGs1Exception;
use Mmuqiitf\FilamentQrCode\Gs1\DigitalLink;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;
use Mmuqiitf\FilamentQrCode\Gs1\SyntaxTable;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

const DL_GTIN = '09521101530001';
const DL_ELEMENTS = [
    '01' => '09521101530001',
    '10' => 'LOT-1',
    '17' => '251231',
];
const DL_PATH_URI = 'https://id.gs1.org/01/09521101530001/10/LOT-1?17=251231';

it('reads Digital Link primary keys and data attributes from the syntax dictionary', function (): void {
    expect(SyntaxTable::rules()['01']->digitalLinkQualifiers)->toBe([['22', '10', '21'], ['235']])
        ->and(SyntaxTable::rules()['00']->digitalLinkQualifiers)->toBe([])
        ->and(SyntaxTable::rules()['17']->digitalLinkAttribute)->toBeTrue()
        ->and(SyntaxTable::rules()['10']->digitalLinkAttribute)->toBeTrue()
        ->and(SyntaxTable::rules()['10']->digitalLinkQualifiers)->toBeNull();
});

it('parses a path-form Digital Link URI into an ElementString', function (): void {
    $parsed = DigitalLink::parse(DL_PATH_URI);

    expect($parsed)->toBeInstanceOf(ElementString::class)
        ->and($parsed->toArray())->toBe(DL_ELEMENTS)
        ->and($parsed->get('01'))->toBe(DL_GTIN)
        ->and($parsed->bracketed())->toBe('(01)09521101530001(10)LOT-1(17)251231');
});

it('parses query-form and mixed Digital Link URIs', function (string $uri): void {
    expect(DigitalLink::parse($uri)->toArray())->toBe(DL_ELEMENTS);
})->with([
    'query only' => ['https://id.gs1.org/?01=09521101530001&10=LOT-1&17=251231'],
    'primary key in the path, attributes and a qualifier in the query' => ['https://example.com/01/09521101530001?10=LOT-1&17=251231'],
    'http and a path prefix' => ['http://brand.example/food/01/09521101530001/10/LOT-1?17=251231'],
]);

it('percent-decodes values and pads a short GTIN to 14 digits', function (): void {
    $parsed = DigitalLink::parse('https://id.gs1.org/01/9521101530001/10/LOT%2B1%2FA');

    expect($parsed->get('01'))->toBe('09521101530001')
        ->and($parsed->get('10'))->toBe('LOT+1/A');
});

it('lets Gs1::parse accept a Digital Link URI', function (): void {
    expect(Gs1::parse(DL_PATH_URI)->toArray())->toBe(DL_ELEMENTS)
        ->and(Gs1::parse('  '.DL_PATH_URI.'  ')->bracketed())->toBe('(01)09521101530001(10)LOT-1(17)251231');
});

it('builds a canonical Digital Link URI with qualifiers in the path', function (): void {
    $uri = DigitalLink::build([
        '17' => '251231',
        '01' => DL_GTIN,
        '10' => 'LOT-1',
    ]);

    expect($uri)->toBe(DL_PATH_URI)
        ->and(DigitalLink::parse($uri)->toArray())->toBe(DL_ELEMENTS);
});

it('builds from an ElementString and a domain override, including a path prefix', function (): void {
    $elements = Gs1::build(DL_ELEMENTS);

    expect(DigitalLink::build($elements, 'https://brand.example/gtin/'))
        ->toBe('https://brand.example/gtin/01/09521101530001/10/LOT-1?17=251231');
});

it('reads the default domain from config', function (): void {
    config(['qr-code.gs1.digital_link.domain' => 'https://codes.example']);

    expect(DigitalLink::build(['01' => DL_GTIN]))->toBe('https://codes.example/01/09521101530001');
});

it('percent-encodes reserved characters in the path and query', function (): void {
    $uri = DigitalLink::build([
        '01' => DL_GTIN,
        '10' => 'LOT+1/A',
        '240' => 'A&B',
    ]);

    expect($uri)->toBe('https://id.gs1.org/01/09521101530001/10/LOT%2B1%2FA?240=A%26B')
        ->and(DigitalLink::parse($uri)->get('10'))->toBe('LOT+1/A')
        ->and(DigitalLink::parse($uri)->get('240'))->toBe('A&B');
});

it('places remaining data attributes in a stable query string', function (): void {
    $uri = DigitalLink::build([
        '01' => '09506000134352',
        '3103' => '001250',
        '17' => '261231',
        '10' => 'ABC',
    ]);

    expect($uri)->toBe('https://id.gs1.org/01/09506000134352/10/ABC?17=261231&3103=001250');
});

it('ignores non-AI query parameters such as linkType', function (): void {
    $parsed = DigitalLink::parse(DL_PATH_URI.'&linkType=gs1:pip&foo=bar');

    expect($parsed->toArray())->toBe(DL_ELEMENTS);
});

it('rejects Digital Link URIs that break the standard rules', function (string $uri, string $reason): void {
    try {
        DigitalLink::parse($uri);
    } catch (InvalidGs1Exception $e) {
        expect($e->value)->toBe($uri)
            ->and($e->getMessage())->toContain($reason);

        return;
    }

    test()->fail('No InvalidGs1Exception thrown.');
})->with([
    'wrong check digit' => ['https://id.gs1.org/01/09521101530002', 'check digit'],
    'unknown AI in the query' => ['https://id.gs1.org/01/09521101530001?05=1234', 'unknown application identifier'],
    'qualifier in the wrong order' => ['https://id.gs1.org/01/09521101530001/10/LOT-1/22/AB', 'order'],
    'mixed qualifier alternatives' => ['https://id.gs1.org/01/09521101530001/10/LOT-1/235/SERIAL', 'qualifier'],
    'no primary key' => ['https://id.gs1.org/10/LOT-1?17=251231', 'primary key'],
    'not a URI' => ['id.gs1.org/01/09521101530001', 'Digital Link'],
    'compressed-looking extra path segment' => ['https://id.gs1.org/01/09521101530001/ASadw', 'not supported'],
]);

it('rejects a build that cannot make a valid Digital Link URI', function (array $elements, string $reason): void {
    try {
        DigitalLink::build($elements);
    } catch (InvalidGs1Exception $e) {
        expect($e->getMessage())->toContain($reason);

        return;
    }

    test()->fail('No InvalidGs1Exception thrown.');
})->with([
    'no primary key' => [['10' => 'LOT-1', '17' => '251231'], 'primary key'],
    'two primary keys' => [['01' => DL_GTIN, '00' => '006141411234567890'], 'one primary key'],
    'mixed qualifier alternatives' => [['01' => DL_GTIN, '10' => 'LOT-1', '235' => 'SERIAL'], 'qualifier'],
    'wrong check digit' => [['01' => '09521101530002'], 'check digit'],
]);

it('encodes a Digital Link QR through QrCodeService', function (): void {
    $fromBuilder = QrCodeService::make()->digitalLink(DL_ELEMENTS)->getRaw();
    $fromUri = QrCodeService::make()->generate(DL_PATH_URI)->getRaw();

    expect($fromBuilder)->toBe($fromUri)
        ->and($fromBuilder)->toContain('<svg');
});
