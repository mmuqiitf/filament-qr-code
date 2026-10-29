# Getting started

> Back to [README](../README.md) · [Scanning](scanning.md) · [Generating](generating.md) · [Reference](reference.md) · [FAQ](faq.md)

This page takes you from `composer require` to a field that checks scans on the server. Each step lists what you should see before you move on.

Requirements: PHP `^8.2`, Laravel 12 or 13, and Filament v5. Camera scanning needs a secure context (`https` or `localhost`). PNG output needs `gd` or `imagick`.

## 1. Install the package

```bash
composer require mmuqiitf/filament-qr-code
```

The service provider is auto-discovered. It registers the JS and CSS with Filament for every panel.

## 2. Register the plugin

Registering the plugin is optional, because the assets load without it. It keeps the setup explicit, and `FilamentQrCodePlugin::get()` needs it.

```php
use Filament\Panel;
use Mmuqiitf\FilamentQrCode\FilamentQrCodePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentQrCodePlugin::make());
}
```

## 3. Add the package views to your theme

The package CSS only styles the scanner chrome (viewfinder, reticle, flash states). Buttons, overlays and layout use Tailwind utility classes in the package views, and Tailwind only generates classes found in the files it scans. Create a theme if the panel has none (`php artisan make:filament-theme`), then add the package views to its `@source` list:

```css
/* resources/css/filament/admin/theme.css */
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament';
@source '../../../../resources/views/filament';
@source '../../../../vendor/mmuqiitf/filament-qr-code/resources/views/**/*';
```

Run `npm run build` afterwards. If you skip this step, the Start/Stop buttons are unstyled, overlays collapse, and the modal looks different from the rest of the panel.

## 4. Publish the assets

```bash
php artisan filament:assets
```

This copies the scanner bundle and the camera decoder into `public/`. The decoder is a separate module, about 120 KB gzipped. It loads on first camera use, so pages that only render QR images never fetch it. Run the command again after every package update.

## 5. Add your first field

```php
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

public static function configure(Schema $schema): Schema
{
    return $schema->components([
        QrScanner::make('sku')
            ->label('SKU')
            ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128]),
    ]);
}
```

`formats()` limits the camera to the symbologies you actually scan. That speeds up decoding and cuts false positives. The cases are `QrCode`, `Aztec`, `DataMatrix`, `Maxicode`, `Codabar`, `Code39`, `Code93`, `Code128`, `Itf`, `Ean13`, `Ean8`, `UpcA` and `UpcE`.

**What you should see:**

- A text input with a camera button.
- The button opens a modal viewfinder after the browser asks for camera permission. Over plain `http`, the modal explains that it needs a secure context.
- The modal has an image-upload fallback.
- A decoded code fills the input and closes the modal.
- A USB or Bluetooth gun (a keyboard-wedge Hardware Scanner) typing into the focused input fills it too. The gun's `Enter` is swallowed, so the form doesn't submit.

## 6. Check the scan on the server

Live scans write straight into the Livewire state. To accept only codes that match a record, add a lookup. A scan that matches nothing is rejected before it is kept:

```php
use App\Models\Product;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

QrScanner::make('sku')
    ->lookup(Product::class, 'sku')
    ->onScanned(fn (string $code, $record) => logger()->info("Scanned {$record?->name}"));
```

Accepted scans flash green and rejected ones flash red with a beep and vibration. More checks (`validateUsing()`, `rejectWhen()`, `pipe()`) are in [Server-side checks](scanning.md#server-side-checks).

## 7. Tune the defaults (optional)

```bash
php artisan vendor:publish --tag="filament-qr-code-config"
```

`config/qr-code.php` holds the defaults for the camera, the Hardware Scanner Interceptor, feedback, sequences, the generator and auditing. Every key is in [Reference](reference.md#configuration). A setter on a component always wins over config.

## When something is off

```bash
php artisan qr-code:doctor
```

The doctor checks the image backends, fonts, published assets, config and routes. The [Troubleshooting table](../README.md#troubleshooting) and the [FAQ](faq.md) cover the usual symptoms.

## Where next

- Pick a component: [Which component do I need?](../README.md#which-component-do-i-need)
- Guns without a camera UI: [Station Listener](scanning.md#station-listener-qrhardwarescannerlistener)
- One camera filling many fields: [Scan Sequence Container](scanning.md#scan-sequence-container-qrscansequence)
- Many scans into one list: [Batch Collector Scanning](collecting.md)
- Showing QR codes: [Generating](generating.md)
