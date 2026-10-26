<?php

declare(strict_types=1);

require_once __DIR__.'/DocHelpers.php';

/**
 * @return array<string, string>
 */
function qrBoostSnippets(string $path): array
{
    preg_match_all('/<code-snippet name="([^"]+)" lang="php">\n(.*?)<\/code-snippet>/s', file_get_contents(qrDocsPath($path)), $matches, PREG_SET_ORDER);

    return array_column(array_map(fn (array $match): array => [$match[1], $match[2]], $matches), 1, 0);
}

it('ships a single self-contained Boost guideline', function (): void {
    $guideline = file_get_contents(qrDocsPath('resources/boost/guidelines/core.blade.php'));

    expect(glob(qrDocsPath('resources/boost/guidelines/*')))->toHaveCount(1)
        ->and($guideline)->toContain('## Filament QR Code')
        ->and($guideline)->not->toContain('@include')
        ->and(substr_count($guideline, '@verbatim'))->toBe(substr_count($guideline, '@endverbatim'));
});

it('parses every PHP snippet in the Boost guideline and skill', function (string $path): void {
    $snippets = qrBoostSnippets($path);

    expect($snippets)->not->toBeEmpty();

    foreach ($snippets as $name => $code) {
        expect(qrParseSnippet($code))->toBeTrue("{$path}: snippet \"{$name}\" does not parse");

        preg_match_all('/^use (Mmuqiitf\\\\FilamentQrCode\\\\[\w\\\\]+);/m', $code, $imports);

        foreach ($imports[1] as $class) {
            expect(class_exists($class) || enum_exists($class) || interface_exists($class))->toBeTrue("{$path}: {$class} does not exist");
        }
    }
})->with([
    'guideline' => 'resources/boost/guidelines/core.blade.php',
    'skill' => 'resources/boost/skills/filament-qr-code/SKILL.md',
]);

it('gives the Boost skill the required frontmatter', function (): void {
    $skill = file_get_contents(qrDocsPath('resources/boost/skills/filament-qr-code/SKILL.md'));

    expect($skill)->toMatch('/\A---\nname: filament-qr-code\ndescription: .+\n---\n/');
});
