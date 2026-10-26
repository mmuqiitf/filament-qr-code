<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

require_once __DIR__.'/DocHelpers.php';

/**
 * @return array<string, string> short name => fully qualified name
 */
function qrPackageClasses(): array
{
    $classes = [];

    foreach (Finder::create()->files()->in(qrDocsPath('src'))->name('*.php') as $file) {
        $relative = str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
        $classes[$file->getBasename('.php')] = 'Mmuqiitf\\FilamentQrCode\\'.$relative;
    }

    return $classes;
}

it('imports every package class a README or docs snippet uses', function (): void {
    $failures = [];

    foreach (qrPhpBlocks() as $where => $code) {
        if (str_starts_with($where, 'UPGRADE.md')) {
            continue;
        }

        foreach (qrPackageClasses() as $short => $class) {
            $used = preg_match('/(?<![\\\\\w$>])'.$short.'(::|\s+\$|\s*\)|\s*\|)/', $code) === 1
                || preg_match('/\bnew '.$short.'\b/', $code) === 1;

            if (! $used || preg_match('/\b(class|enum|interface|trait) '.$short.'\b/', $code) === 1) {
                continue;
            }

            if (! str_contains($code, 'use '.$class.';')) {
                $failures[] = "{$where}: use {$class};";
            }
        }
    }

    expect($failures)->toBe([]);
});
