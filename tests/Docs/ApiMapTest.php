<?php

declare(strict_types=1);

use function Mmuqiitf\FilamentQrCode\Bin\generateApiMarkdown;
use function Mmuqiitf\FilamentQrCode\Bin\generateLlmsFull;

require_once __DIR__.'/DocHelpers.php';
require_once dirname(__DIR__, 2).'/bin/generate-api-docs.php';

it('keeps docs/api.md in sync with src and config', function (): void {
    expect(file_get_contents(qrDocsPath('docs/api.md')))
        ->toBe(generateApiMarkdown(), 'docs/api.md is stale: run `composer docs`');
});

it('keeps llms-full.txt in sync with the docs', function (): void {
    expect(file_get_contents(qrDocsPath('llms-full.txt')))
        ->toBe(generateLlmsFull(), 'llms-full.txt is stale: run `composer docs`');
});

it('gives every public method in the api map a purpose', function (): void {
    preg_match_all('/^\| `(.+)` \|  \|$/m', file_get_contents(qrDocsPath('docs/api.md')), $matches);

    expect($matches[1])->toBe([], 'add a docblock summary to these methods');
});

it('leaves internal members out of the api map', function (): void {
    $api = file_get_contents(qrDocsPath('docs/api.md'));

    foreach (['QrRenderSpec ', 'ScanCheckPipeline ', 'toClient(', 'getRenderSpec(', 'checkPipeline('] as $internal) {
        expect($api)->not->toContain('`'.$internal);
    }
});

it('links every doc page from llms.txt', function (): void {
    $index = file_get_contents(qrDocsPath('llms.txt'));

    expect($index)->toStartWith('# Filament QR Code');

    foreach (['README.md', 'llms-full.txt', ...array_map(fn (string $file): string => substr($file, strlen(qrDocsPath(''))), qrDocsMarkdownFiles(rtrim(qrDocsPath(''), '/')))] as $file) {
        expect($index)->toContain($file);
    }
});
