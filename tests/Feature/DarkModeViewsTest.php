<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

/*
 * Surface color utilities in package Blade views must have a dark: counterpart,
 * come from a Filament component, or sit on an explicit allowlist:
 *
 * - QR/barcode quiet zones (`fi-qr-quiet-zone` + `bg-white`) stay light so
 *   codes remain scannable on a dark panel.
 * - Camera and modal overlays are always dark (bg-gray-950/*, text-white).
 * - Saturated status dots (bg-success-400/500) read on both themes.
 * - Print views stay light and are excluded.
 */

/** @return list<string> */
function darkModeClassLists(string $contents): array
{
    $contents = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $contents);

    $lists = [];

    if (preg_match_all('/\b(?:x-)?(?:class|:class)\s*=\s*"([^"]*)"/s', $contents, $double) === 1 || isset($double[1])) {
        foreach ($double[1] as $value) {
            $lists = [...$lists, ...darkModeListsFromAttribute((string) $value)];
        }
    }

    if (preg_match_all("/\\b(?:x-)?(?:class|:class)\\s*=\\s*'([^']*)'/s", $contents, $single) === 1 || isset($single[1])) {
        foreach ($single[1] as $value) {
            $lists = [...$lists, ...darkModeListsFromAttribute((string) $value)];
        }
    }

    if (preg_match_all('/@class\(\[(.*?)\]\)/s', $contents, $arrays) === 1 || isset($arrays[1])) {
        foreach ($arrays[1] as $body) {
            if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', (string) $body, $quoted) === 1 || isset($quoted[1])) {
                foreach ($quoted[1] as $value) {
                    $lists[] = (string) $value;
                }
            }
        }
    }

    return $lists;
}

/** @return list<string> */
function darkModeListsFromAttribute(string $value): array
{
    $trimmed = trim($value);

    if ($trimmed === '') {
        return [];
    }

    // Alpine / JS expressions: use the quoted class literals inside.
    if (str_contains($trimmed, '?') || str_contains($trimmed, ':') || str_contains($trimmed, '+') || str_contains($trimmed, '(')) {
        $lists = [];

        if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $trimmed, $quoted) === 1 || isset($quoted[1])) {
            foreach ($quoted[1] as $literal) {
                $lists[] = (string) $literal;
            }
        }

        return $lists === [] ? [$trimmed] : $lists;
    }

    return [$trimmed];
}

/** @return list<string> */
function darkModeColorTokens(string $classList): array
{
    preg_match_all(
        '/(?<![\\w-])((?:(?:hover|focus|active|disabled|group-hover|focus-visible|placeholder):)+)?(bg|text|border|ring|divide|outline|fill|stroke|placeholder|decoration)-(slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|primary|success|warning|danger|info|white|black)(?:-\\d{2,3}|\\/\\d+)?(?![\\w-])/',
        $classList,
        $matches,
    );

    return $matches[0] ?? [];
}

function darkModeHasCounterpart(string $token, string $classList): bool
{
    if (str_starts_with($token, 'dark:') || str_contains($token, 'dark:')) {
        return true;
    }

    // hover:text-danger-600 -> dark:hover:text-danger-...
    $parts = [];
    if (preg_match('/^((?:(?:hover|focus|active|disabled|group-hover|focus-visible|placeholder):)*)(bg|text|border|ring|divide|outline|fill|stroke|placeholder|decoration)-/', $token, $m) !== 1) {
        return false;
    }

    $pattern = '/(?<![\\w-])dark:'.preg_quote($m[1].$m[2], '/').'-/';

    return preg_match($pattern, $classList) === 1;
}

function darkModeIsAllowlisted(string $token, string $classList): bool
{
    $alwaysDarkOverlays = [
        'bg-gray-950',
        'bg-gray-950/60',
        'bg-gray-950/70',
        'bg-gray-950/80',
        'bg-gray-950/90',
        'hover:bg-white/10',
        'border-t-transparent',
    ];

    if (in_array($token, $alwaysDarkOverlays, true)) {
        return true;
    }

    // Text on an always-dark overlay (camera chrome, modal backdrop).
    $overlaySurfaces = ['bg-gray-950', 'bg-gray-950/60', 'bg-gray-950/70', 'bg-gray-950/80', 'bg-gray-950/90'];
    if (in_array($token, ['text-white', 'text-danger-400', 'text-warning-400', 'border-primary-500'], true)) {
        foreach ($overlaySurfaces as $surface) {
            if (str_contains($classList, $surface)) {
                return true;
            }
        }

        // Nearby sibling context is handled per-list; camera-controls is a
        // single attribute with bg-gray-950/60 text-white already covered.
    }

    // Theme-independent status indicators.
    if (in_array($token, ['bg-success-400', 'bg-success-500'], true)) {
        return true;
    }

    // Text and chrome that sit on an always-dark overlay (camera status, torch).
    if (str_contains($classList, 'fi-qr-overlay') && in_array($token, ['text-white', 'text-danger-400', 'text-warning-400', 'border-primary-500', 'border-t-transparent'], true)) {
        return true;
    }

    // White label on a saturated success or danger chip / leftover button.
    if ($token === 'text-white' && (str_contains($classList, 'bg-success-') || str_contains($classList, 'bg-danger-'))) {
        return true;
    }

    // Quiet zone around a QR or barcode image: always light, on purpose.
    if ($token === 'bg-white' && str_contains($classList, 'fi-qr-quiet-zone')) {
        return true;
    }

    return false;
}

/** @return list<string> */
function darkModeFilamentOpeningTags(string $contents): array
{
    $tags = [];

    if (preg_match_all('/<x-filament::[^>]+>/s', $contents, $matches) === 1 || isset($matches[0])) {
        foreach ($matches[0] as $tag) {
            $tags[] = (string) $tag;
        }
    }

    return $tags;
}

it('pairs every surface color with a dark variant, a Filament component, or an allowlisted exception', function (): void {
    $offenders = [];
    $filamentClassLists = [];

    foreach (Finder::create()->files()->in(__DIR__.'/../../resources/views')->name('*.blade.php')->notPath('print') as $file) {
        $contents = (string) file_get_contents($file->getRealPath());

        foreach (darkModeFilamentOpeningTags($contents) as $tag) {
            foreach (darkModeClassLists($tag) as $list) {
                $filamentClassLists[$list] = true;
            }
        }

        foreach (darkModeClassLists($contents) as $list) {
            if (isset($filamentClassLists[$list])) {
                continue;
            }

            foreach (darkModeColorTokens($list) as $token) {
                if (str_starts_with($token, 'dark:') || str_contains($token, 'dark:')) {
                    continue;
                }

                if (darkModeHasCounterpart($token, $list) || darkModeIsAllowlisted($token, $list)) {
                    continue;
                }

                $offenders[] = $file->getRelativePathname().': '.$token.' in "'.$list.'"';
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps a light quiet zone around QR and barcode images', function (): void {
    $files = [
        'components/qr-column.blade.php',
        'components/qr-code-display.blade.php',
        'components/qr-entry.blade.php',
        'components/barcode-image.blade.php',
    ];

    foreach ($files as $file) {
        $contents = (string) file_get_contents(__DIR__.'/../../resources/views/'.$file);

        expect($contents)
            ->toContain('fi-qr-quiet-zone')
            ->toContain('bg-white');
    }
});
