<?php

declare(strict_types=1);

it('loads namespaced package translations', function () {
    expect(__('filament-qr-code::ui.scan'))->toBe('Scan')
        ->and(__('filament-qr-code::ui.batch_qr_collector'))->toBe('Batch QR Collector')
        ->and(__('filament-qr-code::ui.camera'))->toBe('Camera:');
});

function packageLangFiles(): array
{
    return glob(dirname(__DIR__, 2).'/resources/lang/*/ui.php') ?: [];
}

/** @return list<string> */
function usedUiKeys(): array
{
    $root = dirname(__DIR__, 2);
    $keys = [];

    foreach (['src', 'resources/views'] as $dir) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            preg_match_all("/filament-qr-code::ui\.([a-z0-9_]+)/", (string) file_get_contents($file->getPathname()), $matches);
            array_push($keys, ...$matches[1]);
        }
    }

    return array_values(array_unique($keys));
}

/** @return array<string, string> */
function jsFallbackMessages(): array
{
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/resources/js/messages.js');
    preg_match_all("/^\s+(\w+): '((?:[^'\\\\]|\\\\.)*)',?$/m", $source, $matches, PREG_SET_ORDER);

    $messages = [];
    foreach ($matches as [, $name, $text]) {
        $messages[$name] = stripslashes($text);
    }

    return $messages;
}

it('defines every used ui key in every lang file', function () {
    $used = usedUiKeys();

    expect($used)->not->toBeEmpty();

    foreach (packageLangFiles() as $file) {
        $defined = array_keys(require $file);

        expect(array_values(array_diff($used, $defined)))->toBe([], "Missing in {$file}");
    }
});

it('keeps every lang file on the same keys, all of them used', function () {
    $files = packageLangFiles();
    $reference = array_keys(require $files[0]);
    sort($reference);

    foreach ($files as $file) {
        $keys = array_keys(require $file);
        sort($keys);

        expect($keys)->toBe($reference, "Key mismatch in {$file}");
    }

    expect(array_values(array_diff($reference, usedUiKeys())))->toBe([]);
});

it('has no duplicate keys in a lang file', function () {
    foreach (packageLangFiles() as $file) {
        preg_match_all("/^\s*'([a-z0-9_]+)'\s*=>/m", (string) file_get_contents($file), $matches);

        expect(array_diff_assoc($matches[1], array_unique($matches[1])))->toBe([], "Duplicate key in {$file}");
    }
});

it('keeps the JS fallback messages equal to the en strings', function () {
    $fallbacks = jsFallbackMessages();
    $map = \Mmuqiitf\FilamentQrCode\Support\ScanMessages::keys();

    expect(array_keys($fallbacks))->toEqualCanonicalizing(array_keys($map));

    foreach ($map as $name => $key) {
        expect($fallbacks[$name])->toBe(trans("filament-qr-code::ui.{$key}", [], 'en'), $name);
    }
});

it('assigns no untranslated sentence to a user-facing message in JS', function () {
    $root = dirname(__DIR__, 2).'/resources/js';
    $offenders = [];
    $literal = "'[A-Z][^']*\\s[^']*'";
    $sinks = [
        "/errorMessage\s*=\s*{$literal}/",
        "/\w+Message\s*[:=]\s*{$literal}/",
        "/\w+Announcement\s*[:=]\s*{$literal}/",
        "/\|\|\s*{$literal}/",
    ];

    foreach (glob($root.'/*.js') as $file) {
        if (str_ends_with($file, '.test.js') || basename($file) === 'messages.js' || str_contains($file, 'harness')) {
            continue;
        }

        foreach (file($file) as $number => $line) {
            foreach ($sinks as $sink) {
                if (preg_match($sink, $line)) {
                    $offenders[] = basename($file).':'.($number + 1).': '.trim($line);

                    break;
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('sends every message in the scan payload of each camera surface', function (string $component) {
    $payload = \Mmuqiitf\FilamentQrCode\Support\ScanOptions::payload($component::make('codes'));

    foreach (array_keys(\Mmuqiitf\FilamentQrCode\Support\ScanMessages::forCamera()) as $name) {
        expect($payload)->toHaveKey($name);
    }
})->with([
    \Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner::class,
    \Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector::class,
    \Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence::class,
    \Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction::class,
]);
