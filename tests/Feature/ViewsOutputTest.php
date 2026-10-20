<?php

declare(strict_types=1);

use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ViewsRenderComponent;

/**
 * Comparable markup: drop Livewire ids, Alpine ids and whitespace, and the
 * additions P2.2 makes on purpose (fi-qr-* classes, data-qr-part markers).
 */
function comparableViewHtml(string $html): string
{
    $html = preg_replace('/wire:id="[^"]*"|wire:snapshot="[^"]*"|wire:effects="[^"]*"|wire:name="[^"]*"/', '', $html);
    $html = preg_replace("/livewireId: '[^']*'/", "livewireId: ''", (string) $html);
    $html = preg_replace('/wire:key="[A-Za-z0-9]{20}\./', 'wire:key="', (string) $html);
    $html = preg_replace('/\s+data-qr-part="[^"]*"/', '', (string) $html);
    $html = preg_replace('/\bfi-qr-[a-z0-9-]+\b/', '', (string) $html);
    $html = preg_replace('/<!--.*?-->/s', '', (string) $html);
    $html = preg_replace('/class="\s*([^"]*?)\s*"/', 'class="$1"', (string) $html);
    $html = preg_replace('/\s{2,}/', ' ', (string) $html);

    return trim((string) preg_replace('/>\s+</', '><', (string) $html));
}

beforeEach(function (): void {
    ViewsRenderComponent::$configure = null;
});

it('renders the same markup as before the view split', function (): void {
    $baseline = __DIR__.'/../Fixtures/views/baseline.html';
    $html = comparableViewHtml(Livewire::test(ViewsRenderComponent::class)->html());

    if (! file_exists($baseline)) {
        file_put_contents($baseline, $html);
    }

    expect($html)->toBe(file_get_contents($baseline));
});
