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
        if ($file->getExtension() === 'md') {
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
it('points every absolute repo image URL at a file that exists on this branch', function (): void {
    $root = dirname(__DIR__, 2);
    $missing = [];

    foreach (docLinkFiles() as $file) {
        preg_match_all('#https://raw\.githubusercontent\.com/mmuqiitf/filament-qr-code/main/([^)\s"\']+)#', (string) file_get_contents($file), $matches);

        foreach ($matches[1] as $path) {
            if (! is_file($root.'/'.$path)) {
                $missing[] = basename($file).': '.$path;
            }
        }
    }

    expect($missing)->toBe([]);
});

it('uses every screenshot in docs/images and gives each one alt text', function (): void {
    $root = dirname(__DIR__, 2);
    $markdown = implode("\n", array_map(fn (string $file): string => (string) file_get_contents($file), docLinkFiles()));

    $images = glob($root.'/docs/images/*.png') ?: [];
    expect($images)->not->toBeEmpty();

    foreach ($images as $image) {
        $name = basename($image);
        expect($markdown)->toMatch('#!\[[^\]]+\]\([^)]*images/'.preg_quote($name, '#').'\)#');
        expect(filesize($image))->toBeLessThan(250 * 1024);
    }
});
