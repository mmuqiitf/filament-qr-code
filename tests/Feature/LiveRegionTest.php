<?php

declare(strict_types=1);

use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ViewsRenderComponent;

function liveRegionsIn(string $html): array
{
    preg_match_all('/<[^>]*data-qr-part="announcer"[^>]*>/', $html, $matches);

    return $matches[0];
}

it('renders one polite, atomic live region per scanner, collector and sequence', function (): void {
    $html = Livewire::test(ViewsRenderComponent::class)->html();
    $regions = liveRegionsIn($html);

    // The fixture renders one scanner, one collector and one sequence.
    expect($regions)->toHaveCount(3);

    foreach ($regions as $region) {
        expect($region)
            ->toContain('role="status"')
            ->toContain('aria-live="polite"')
            ->toContain('aria-atomic="true"')
            ->toContain('x-text="announcement"')
            ->toContain('fi-qr-sr-only');
    }
});

it('renders the live region in the collect action modal', function (): void {
    $html = view('filament-qr-code::components.qr-collector-modal', ['scanPayload' => [], 'actionName' => 'collect'])->render();

    expect(liveRegionsIn($html))->toHaveCount(1);
});

it('leaves the sequence error box to the live region, and camera errors assertive', function (): void {
    $html = Livewire::test(ViewsRenderComponent::class)->html();

    preg_match('/<p[^>]*data-qr-part="sequence.status"[^>]*>/', $html, $status);

    expect($status[0])->not->toContain('role="alert"')
        ->and($html)->toContain('role="alert" x-text="errorMessage"');
});

it('labels the remove buttons of the collector lists', function (): void {
    $field = Livewire::test(ViewsRenderComponent::class)->html();
    $modal = view('filament-qr-code::components.qr-collector-modal', ['scanPayload' => [], 'actionName' => 'collect'])->render();

    foreach ([$field, $modal] as $html) {
        expect($html)->toContain('aria-label="'.__('filament-qr-code::ui.remove_code').'"');
    }
});
