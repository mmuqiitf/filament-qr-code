# Recipe: custom inputs and reusable fields

Three ways to bring your own input into the scanner: feed codes from another device, share the keyboard-wedge router with your own widget, or package a configured scanner as a field class.

## Feed codes from anywhere

An RFID reader, a WebSocket, or a test can push a code into a scanner or a sequence. It goes through exactly the same checks as a scan:

```blade
<div x-data x-on:rfid-read.window="$dispatch('qr-sequence-feed', { field: 'data.station', code: $event.detail.tag, source: 'hardware' })"></div>
```

From PHP (for example after a broadcast event):

```php
$this->dispatch('qr-scanner-feed', field: 'data.scan', code: $tag, source: 'hardware');
```

Or from JavaScript with the element API:

```js
await document.querySelector('[data-qr-sequence]').qrScanSequence.feed('OP-001', 'hardware');
```

`source` is `camera`, `hardware`, `upload` or `manual`; it reaches `ScanResult::$source`, the hooks and the events. Typed (`manual`) codes never skip an optional step.

## Run the checks yourself

A field class (or a Livewire action that holds the field) can run the same normalization and checks a scan gets, without going through the browser:

```php
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;

$code = $field->normalizeScannedCode($rawTag);           // normalizeWith() presets, then normalizeUsing()
$result = $field->runScanChecks($code, ScanInput::Hardware); // rules, lookup, closures, piped stages

if (! $result->passes) {
    // $result->message explains why; $result->record holds the looked-up record otherwise
}
```

`runScanChecks()` expects an already normalized code and stops at the first failing check. Both methods are on `QrScanner` (through `InteractsWithScans`); they don't fire the scan hooks or events.

## Share the keyboard-wedge router

Every wedge burst on the page has one owner. Register your own input so it takes part instead of fighting the scanners for keystrokes:

```js
const unregister = window.FilamentQrCode.router.register({
    id: 'rfid-keypad',
    element: document.querySelector('#rfid'),
    kind: 'custom',
    scope: 'focus',                          // or 'page': also when nothing else is focused
    detection: { minBarcodeLength: 4 },
    accept: (code) => /^\d{10}$/.test(code), // return false to pass the burst on
    deliver: (code) => window.dispatchEvent(new CustomEvent('rfid-read', { detail: { tag: code } })),
});
```

Call `unregister()` when the widget goes away (Alpine's `destroy()`, or `livewire:navigating`).

## A reusable field class

```bash
php artisan make:qr-scan-field SkuScanner            # extends QrScanner
php artisan make:qr-scan-field StationSequence --sequence
php artisan vendor:publish --tag=filament-qr-code-stubs  # optional: edit the stubs first
```

```php
<?php

namespace App\Filament\Forms\Components;

use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

class SkuScanner extends QrScanner
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->lookup(Product::class, 'sku')
            ->normalizeWith(uppercase: true)
            ->mode(ScanMode::Continuous);
    }
}
```

Use it like any field: `SkuScanner::make('scan')->onScanned(...)`. For a setting that should apply to every scanner in the app instead, use `QrScanner::configureUsing()` in a service provider.
