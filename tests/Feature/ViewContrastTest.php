<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
 * Primary text on a light background uses primary-700. primary-600 drops
 * below 4.5:1 on white with an amber primary (Filament's default), and the
 * download links and step labels are small text.
 */
it('keeps primary-coloured text at primary-700 or darker in light mode', function (): void {
    $offenders = [];

    foreach (Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php') as $file) {
        foreach (file($file->getRealPath()) ?: [] as $number => $line) {
            if (preg_match('/(?<![\w:-])(?:hover:)?text-primary-(?:[1-5]\d\d|600)\b/', $line) === 1) {
                $offenders[] = $file->getRelativePathname().':'.($number + 1);
            }
        }
    }

    expect($offenders)->toBe([]);
});
