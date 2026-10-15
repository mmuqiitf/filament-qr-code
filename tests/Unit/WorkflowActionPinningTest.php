<?php

declare(strict_types=1);

it('pins every third-party GitHub Action to a full commit SHA', function () {
    $workflows = glob(__DIR__.'/../../.github/workflows/*.yml') ?: [];

    expect($workflows)->not->toBeEmpty();

    $unpinned = [];

    foreach ($workflows as $workflow) {
        preg_match_all('/^\s*-?\s*uses:\s*([^\s#]+)/m', (string) file_get_contents($workflow), $matches);

        foreach ($matches[1] as $reference) {
            if (str_starts_with($reference, './')) {
                continue;
            }

            if (preg_match('/@[0-9a-f]{40}$/', $reference) === 1) {
                continue;
            }

            $unpinned[] = basename($workflow).": {$reference}";
        }
    }

    expect($unpinned)->toBe([]);
});
