<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

function qrDocsPath(string $path): string
{
    return dirname(__DIR__, 2).'/'.$path;
}

it('documents every browser event the package fires', function () {
    $events = [];

    $files = Finder::create()->files()->in(qrDocsPath('resources/js'))->name('*.js')->notName('*.test.js');

    foreach ($files as $file) {
        preg_match_all("/new CustomEvent\\(\\s*['\"](qr-[a-z0-9-]+)['\"]/", $file->getContents(), $matches);
        array_push($events, ...$matches[1]);
    }

    $events = array_values(array_unique($events));
    $doc = file_get_contents(qrDocsPath('docs/events.md'));

    expect($events)->not->toBeEmpty();

    foreach ($events as $event) {
        expect(str_contains($doc, "`{$event}`"))->toBeTrue("docs/events.md does not mention `{$event}`");
    }
});

it('documents every Laravel event the package dispatches', function () {
    $doc = file_get_contents(qrDocsPath('docs/events.md'));

    foreach (glob(qrDocsPath('src/Events/*.php')) as $file) {
        $class = basename($file, '.php');

        expect(str_contains($doc, $class))->toBeTrue("docs/events.md does not mention {$class}");
    }
});

it('documents the public helpers and keeps internal members tagged', function (): void {
    $root = dirname(__DIR__, 2);
    $docs = file_get_contents($root.'/README.md');
    foreach (glob($root.'/docs/{,recipes/}*.md', GLOB_BRACE) as $file) {
        $docs .= file_get_contents($file);
    }

    foreach (['recordsToFiles', 'flushDataUriCache', 'normalizeScannedCode', 'runScanChecks', 'effectiveCacheTtl'] as $method) {
        expect($docs)->toContain($method.'(');
    }

    foreach (['QrRenderSpec', 'ScanResult', 'SequenceScanResult', 'CodeNormalizer', 'ConfigValue', 'QrZipArchive', 'ScanCheckPipeline'] as $class) {
        $reflection = new ReflectionClass('Mmuqiitf\\FilamentQrCode\\Support\\'.$class);
        $docComment = (string) $reflection->getDocComment();

        if ($class === 'ScanResult') {
            $docComment = (string) $reflection->getMethod('toClient')->getDocComment();
        }

        expect($docComment)->toContain('@internal');
    }
});
