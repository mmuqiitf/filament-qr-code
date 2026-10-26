<?php

declare(strict_types=1);

require_once __DIR__.'/DocHelpers.php';

it('parses every PHP snippet in the docs', function () {
    $failures = [];

    foreach (qrPhpBlocks() as $where => $code) {
        if (! qrParseSnippet($code)) {
            $failures[] = $where;
        }
    }

    expect($failures)->toBe([]);
});

it('imports only package classes that exist', function () {
    $failures = [];

    foreach (qrPhpBlocks() as $where => $code) {
        // "Before" halves of upgrade snippets name classes that are gone on purpose.
        $code = preg_replace('#// Before.*?// After#s', '', $code);

        preg_match_all('/^use (?:function )?(Mmuqiitf\\\\FilamentQrCode\\\\[\w\\\\]+)/m', $code, $matches);

        foreach ($matches[1] as $name) {
            $exists = class_exists($name) || interface_exists($name) || trait_exists($name) || enum_exists($name) || function_exists($name);

            if (! $exists) {
                $failures[] = "{$where}: {$name}";
            }
        }
    }

    expect($failures)->toBe([]);
});
