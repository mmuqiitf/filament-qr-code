# Filament QR Code Scanner

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mmuqiitf/filament-qr-code/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mmuqiitf/filament-qr-code/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mmuqiitf/filament-qr-code/fix-php-code-style.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mmuqiitf/filament-qr-code/actions?query=workflow%3A"Fix+PHP+code+style"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)

A comprehensive Filament package for QR code and barcode scanning. This package provides form components, actions, and pages for integrating QR code scanning into your Filament applications.

## Features

- 📷 **QrCodeScannerInput** - Text input with QR scanner modal
- 🎬 **QrCodeScannerAction** - Standalone action for any component
- 📄 **QrCodeScannerPage** - Pre-built page for scanning workflows
- 📱 **Mobile Optimized** - Auto-selects back camera on mobile devices
- 🔊 **Feedback** - Beep sound and vibration on successful scan
- 🌙 **Dark Mode** - Full dark mode support
- 🌐 **Multi-language** - English and Indonesian translations included
- 📊 **Multiple Formats** - QR, EAN-13, EAN-8, Code128, Code39, UPC-A, UPC-E

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Filament 3.2+ or 4.0+

## Installation

Install the package via Composer:

```bash
composer require mmuqiitf/filament-qr-code
```

Run the install command (optional):

```bash
php artisan qr-code:install
```

Or publish configuration manually:

```bash
php artisan vendor:publish --tag=filament-qr-code-config
```

### Build Assets

The package uses [html5-qrcode](https://github.com/mebjas/html5-qrcode) for scanning. If you need to customize the JavaScript, install dependencies and build:

```bash
cd vendor/mmuqiitf/filament-qr-code
npm install
npm run build
```

## Usage

### Register the Plugin (Optional)

To enable the built-in scanner page, register the plugin in your panel:

```php
use Mmuqiitf\FilamentQrCode\QrCodePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            QrCodePlugin::make()
                ->qrCodeScannerPage(), // Enable built-in page
        ]);
}
```

### QrCodeScannerInput - Form Component

Add a text input with QR scanner capability:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeScannerInput;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            QrCodeScannerInput::make('barcode')
                ->label('Product Barcode')
                ->required(),
        ]);
}
```

#### Available Options

```php
QrCodeScannerInput::make('serial_number')
    ->label('Serial Number')
    ->useBackCamera()           // or ->useFrontCamera()
    ->continuous()              // Keep scanning after each scan
    ->scanDelay(2000)           // Debounce delay in ms
    ->fps(15)                   // Camera FPS (lower = less CPU)
    ->qrboxSize(300)            // Scanning area size
    ->modalWidth('3xl')         // Modal size
    ->slideOver()               // Use slide-over modal
    ->beepOnScan(true)          // Play sound on scan
    ->vibrateOnScan(true)       // Vibrate on scan (mobile)
    ->showPreview()             // Show scanned value preview
    ->hidePreview()             // Hide scanned value preview
```

### QrCodeScannerAction - Standalone Action

Use in table actions, page header actions, or anywhere:

```php
use Mmuqiitf\FilamentQrCode\Actions\QrCodeScannerAction;

protected function getHeaderActions(): array
{
    return [
        QrCodeScannerAction::make()
            ->label('Scan Product')
            ->targetField('data.product_id')
            ->useBackCamera()
            ->continuous(),
    ];
}
```

### Custom Page with Multi-Field Scanning

Create a custom scanning workflow:

```php
<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrScanner;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;

class InventoryPage extends Page
{
    use HasQrScanner;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static string $view = 'filament.pages.inventory-page';

    public ScanMode $scanMode = ScanMode::Sequence;

    public function mount(): void
    {
        $this->mountHasQrScanner();
    }

    public function getScanFields(): array
    {
        return [
            ['key' => 'location', 'label' => 'Location'],
            ['key' => 'product', 'label' => 'Product'],
            ['key' => 'batch', 'label' => 'Batch Number'],
        ];
    }

    protected function afterQrCodeScanned(string $value, string $field): void
    {
        // Custom logic after each scan
        if ($field === 'product') {
            $product = \App\Models\Product::where('barcode', $value)->first();
            if (!$product) {
                $this->addError('product', 'Product not found');
                $this->clearScanResult('product');
            }
        }
    }

    public function submit(): void
    {
        // Process all scanned values
        $data = $this->scanResults;
        // Create your record...
    }
}
```

With Blade view:

```blade
<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            @include('filament-qr-code::components.embedded-scanner', [
                'cameraFacing' => $cameraFacing,
                'scanMode' => $scanMode,
            ])
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            @foreach($this->getScanFields() as $field)
                <div class="p-3 rounded-lg {{ $currentScanField === $field['key'] ? 'ring-2 ring-primary-500' : '' }}">
                    <span class="text-sm text-gray-500">{{ $field['label'] }}</span>
                    <p class="font-mono">{{ $scanResults[$field['key']] ?? '—' }}</p>
                </div>
            @endforeach

            <x-filament::button wire:click="submit" class="mt-4 w-full">
                Submit
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=filament-qr-code-config
```

Available options in `config/qr-code.php`:

```php
return [
    'camera_facing' => 'environment',  // 'user', 'environment', 'auto'
    'scan_mode' => 'single',           // 'single', 'continuous', 'sequence'
    'scan_delay' => 1500,              // Debounce in milliseconds
    'fps' => 30,                       // Camera frames per second
    'qrbox_size' => 250,               // Scanning area size in pixels
    'show_preview' => true,            // Show last scanned value
    'modal_width' => '2xl',            // Modal size
    'beep_on_scan' => true,            // Play beep sound
    'vibrate_on_scan' => true,         // Vibrate on mobile
];
```

## Customization

### Views

Publish views for customization:

```bash
php artisan vendor:publish --tag=filament-qr-code-views
```

### Translations

Publish translation files:

```bash
php artisan vendor:publish --tag=filament-qr-code-translations
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Muhammad Muqiit Faturrahman](https://github.com/mmuqiitf)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
