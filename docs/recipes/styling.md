# Recipe: styling a scanner or sequence

From least to most invasive: CSS classes and variables, render hooks, then a published sub-view. Prefer the first two; they survive package updates.

The example app's manufacturing station (`/admin/manufacturing`) uses all three.

## Classes and CSS variables

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

QrScanSequence::make()
    ->statePath('station')
    ->extraAttributes(['class' => 'mfg-station']),
```

```css
/* resources/css/filament/admin/theme.css */
.mfg-station {
    --fi-qr-viewfinder-radius: 1.25rem;
    --fi-qr-reticle-color: #f59e0b;
    --fi-qr-laser-color: #f59e0b;
    --fi-qr-flash-success: rgb(16 185 129);
}

/* Each step row is .fi-qr-sequence-step with data-qr-step-active="true|false". */
.mfg-station .fi-qr-sequence-step[data-qr-step-active='false'] {
    text-decoration: line-through;
}
```

The full variable list is in [Customizing](../customizing.md). Parts carry `data-qr-part` attributes (`sequence.step`, …), which are also safe to target in tests.

## Render hooks

Add markup at fixed points without touching the views. Scope a hook to one field by its state path:

```php
use Filament\Support\Facades\FilamentView;
use Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook;

FilamentView::registerRenderHook(
    QrCodeRenderHook::SEQUENCE_STEPS_AFTER,
    fn (): View => view('filament.hooks.mfg-legend'),
    scopes: 'data.station',
);
```

Hooks: `SCANNER_START`, `SCANNER_END`, `SCANNER_MODAL_START`, `SCANNER_MODAL_END`, `SEQUENCE_START`, `SEQUENCE_END`, `SEQUENCE_STEPS_BEFORE`, `SEQUENCE_STEPS_AFTER` `SEQUENCE_STEP_HEADER` (the same markup in every step header: steps render client-side, so it can't vary per step), and `COLLECTOR_START` / `COLLECTOR_END`.

## A published sub-view

When markup has to change, publish the views and keep only the file you edit:

```bash
php artisan vendor:publish --tag=filament-qr-code-views
# keep resources/views/vendor/filament-qr-code/components/sequence/step-chip.blade.php, delete the rest
```

Keep the Alpine bindings the package relies on (`results`, `currentFieldIndex`, `idx`, `isSticky(...)`) and add to them, e.g. a pin on sticky steps:

```blade
<span data-mfg-sticky-pin x-show="isSticky(f.key)" x-cloak>📌</span>
```

A published view applies to every scanner and sequence in the app and stops following package changes: diff it against the package's copy after each upgrade (see [UPGRADE](../../UPGRADE.md)).
