<?php

declare(strict_types=1);

require_once __DIR__.'/DocHelpers.php';

/**
 * @param  array<array-key, mixed>  $config
 * @return list<list<string>>
 */
function qrConfigPaths(array $config, array $prefix = []): array
{
    $paths = [];

    foreach ($config as $key => $value) {
        $path = [...$prefix, (string) $key];
        $paths[] = $path;

        if (is_array($value) && $value !== [] && ! array_is_list($value)) {
            array_push($paths, ...qrConfigPaths($value, $path));
        }
    }

    return $paths;
}

function qrReferenceSection(string $group): string
{
    $reference = file_get_contents(qrDocsPath('docs/reference.md'));
    $pattern = '/^### `'.preg_quote($group, '/').'`\n(.*?)(?=^##(?:#)? |\z)/ms';

    return preg_match($pattern, $reference, $match) === 1 ? $match[1] : '';
}

it('documents every config key in docs/reference.md', function (): void {
    $config = require qrDocsPath('config/qr-code.php');
    $missing = [];

    foreach (qrConfigPaths($config) as $path) {
        $group = array_shift($path);
        $section = qrReferenceSection($group);

        if ($section === '') {
            $missing[] = $group;

            continue;
        }

        if ($path === []) {
            continue;
        }

        // A group key counts as documented when one of its children is.
        $documented = str_contains($section, '`'.implode('.', $path).'.');

        for ($length = count($path); $length > 0; $length--) {
            if (str_contains($section, '`'.implode('.', array_slice($path, 0, $length)).'`')) {
                $documented = true;

                break;
            }
        }

        if (! $documented) {
            $missing[] = $group.'.'.implode('.', $path);
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
