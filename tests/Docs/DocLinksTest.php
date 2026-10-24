<?php

declare(strict_types=1);

function docLinkSlug(string $heading): string
{
    $slug = mb_strtolower(trim($heading));
    $slug = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $slug);

    return str_replace(' ', '-', $slug);
}

function docLinkAnchors(string $file): array
{
    $markdown = preg_replace('/^```.*?^```/ms', '', file_get_contents($file));
    preg_match_all('/^#{1,6}\s+(.+?)\s*$/m', $markdown, $matches);

    return array_map(docLinkSlug(...), $matches[1]);
}

function docLinkFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [$root.'/README.md', $root.'/UPGRADE.md', $root.'/CHANGELOG.md'];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/docs')) as $file) {
        if ($file->getExtension() === 'md' && ! str_contains($file->getPathname(), '/superpowers/')) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

it('resolves every relative markdown link and anchor in the docs', function (): void {
    $broken = [];

    foreach (docLinkFiles() as $file) {
        $markdown = preg_replace('/^```.*?^```/ms', '', file_get_contents($file));
        preg_match_all('/\]\(([^)\s]+)\)/', $markdown, $matches);

        foreach ($matches[1] as $link) {
            if (preg_match('#^(https?:|mailto:|\.\./\.\./)#', $link)) {
                continue;
            }

            [$path, $anchor] = array_pad(explode('#', $link, 2), 2, null);
            $target = $path === '' ? $file : realpath(dirname($file).'/'.$path);

            if ($target === false) {
                $broken[] = basename($file).': '.$link;

                continue;
            }

            if ($anchor !== null && str_ends_with($target, '.md') && ! in_array($anchor, docLinkAnchors($target), true)) {
                $broken[] = basename($file).': '.$link;
            }
        }
    }

    expect($broken)->toBe([]);
});
