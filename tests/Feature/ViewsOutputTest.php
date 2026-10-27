<?php

declare(strict_types=1);

use Filament\Support\Facades\FilamentView;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ViewsRenderComponent;
use Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook;

/**
 * Comparable markup: drop Livewire ids, Alpine ids and whitespace, and the
 * additions P2.2 makes on purpose (fi-qr-* classes, data-qr-part markers).
 */
function comparableViewHtml(string $html): string
{
    $html = preg_replace('/wire:id="[^"]*"|wire:snapshot="[^"]*"|wire:effects="[^"]*"|wire:name="[^"]*"/', '', $html);
    $html = preg_replace("/livewireId: '[^']*'/", "livewireId: ''", (string) $html);
    $html = preg_replace('/wire:key="[A-Za-z0-9]{20}\./', 'wire:key="', (string) $html);
    // Livewire's deterministic Blade keys hash the view's absolute path (lw-<crc32>-<n>): keep the index, drop the hash.
    $html = preg_replace('/\blw-\d+-(\d+)\b/', 'lw-hash-$1', (string) $html);
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

    // Whitespace inside a tag is insignificant; the split moves some closing '>' onto their own line.
    $collapse = fn (string $markup): string => (string) preg_replace(['/\s+/', '/\s+>/'], [' ', '>'], $markup);

    expect($collapse($html))->toBe($collapse((string) file_get_contents($baseline)));
});

it('marks every part with an fi-qr-* class and a data-qr-part marker', function (): void {
    $html = Livewire::test(ViewsRenderComponent::class)->html();

    foreach (['scanner', 'scanner-input', 'scanner-camera-button', 'scanner-status', 'scanner-modal', 'scanner-modal-camera',
        'scanner-modal-actions', 'sequence', 'sequence-header', 'sequence-camera', 'sequence-status', 'sequence-steps',
        'sequence-step', 'sequence-step-chip', 'sequence-step-value', 'collector', 'collector-header', 'collector-items'] as $part) {
        expect($html)->toContain('fi-qr-'.$part);
    }

    expect($html)->toContain('data-qr-part="sequence.step-chip"');
});

it('merges extraAttributes onto each root', function (): void {
    ViewsRenderComponent::$configure = fn (array $components): array => array_map(
        fn ($component) => $component->extraAttributes(['class' => 'my-extra', 'data-test' => 'x']),
        $components,
    );

    $html = Livewire::test(ViewsRenderComponent::class)->html();

    expect(substr_count($html, 'my-extra'))->toBe(3)
        ->and(substr_count($html, 'data-test="x"'))->toBe(3)
        ->and($html)->toMatch('/class="fi-qr-scanner relative rounded-lg my-extra"/');
});

it('renders hook content, scoped to the Livewire class and the field state path', function (): void {
    FilamentView::registerRenderHook(QrCodeRenderHook::SEQUENCE_STEP_HEADER, fn (): string => '<i>step-hook</i>', scopes: 'data.receipt');
    FilamentView::registerRenderHook(QrCodeRenderHook::SCANNER_START, fn (): string => '<i>scanner-hook</i>', scopes: ViewsRenderComponent::class);
    FilamentView::registerRenderHook(QrCodeRenderHook::COLLECTOR_END, fn (): string => '<i>other-scope</i>', scopes: 'data.nope');

    $html = Livewire::test(ViewsRenderComponent::class)->html();

    expect($html)->toContain('<i>step-hook</i>')
        ->and($html)->toContain('<i>scanner-hook</i>')
        ->and($html)->not->toContain('other-scope');
});

it('themes the scan UI through CSS variables with the old values as fallbacks', function (): void {
    $css = (string) file_get_contents(__DIR__.'/../../resources/css/qr-code.css');

    foreach (['--fi-qr-viewfinder-bg, #000', '--fi-qr-viewfinder-radius, 0.75rem', '--fi-qr-reticle-color, #22c55e',
        '--fi-qr-reticle-size, 220px', '--fi-qr-laser-color, #22c55e', '--fi-qr-flash-success, rgb(34 197 94)',
        '--fi-qr-flash-danger, rgb(239 68 68)'] as $declaration) {
        expect($css)->toContain('var('.$declaration.')');
    }
});

it('gives every camera viewfinder a loading and error overlay with retry', function (): void {
    $html = Livewire::test(ViewsRenderComponent::class)->html();

    // scanner modal, scan sequence and inline collector
    expect(substr_count($html, 'data-qr-part="camera.status"'))->toBe(3)
        ->and($html)->toContain('@click="retryCamera()"');

    $sequenceCamera = (string) str($html)->after('data-qr-part="sequence.camera"')->before('data-qr-part="sequence.status"');
    expect($sequenceCamera)->toContain('data-qr-part="camera.status"');
});

it('shows a paused banner with a resume button on the scan sequence', function (): void {
    $html = Livewire::test(ViewsRenderComponent::class)->html();
    $paused = (string) str($html)->after('data-qr-part="sequence.paused"')->before('</div>');

    expect($html)->toContain('data-qr-part="sequence.paused"')
        ->and($paused)->toContain('Scanning paused.')
        ->and($paused)->toContain('@click="resume()"')
        ->and($paused)->toContain('Resume scanning');
});
