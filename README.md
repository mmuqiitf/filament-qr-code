# Filament QR Code

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mmuqiitf/filament-qr-code/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mmuqiitf/filament-qr-code/actions?query=workflow%3Arun-tests+branch%3Amain)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg?style=flat-square)](https://phpstan.org/)
[![Total Downloads](https://img.shields.io/packagist/dt/mmuqiitf/filament-qr-code.svg?style=flat-square)](https://packagist.org/packages/mmuqiitf/filament-qr-code)

A powerful, modern QR code package designed exclusively for **Filament v5** and **Laravel 11 / 12**.

Features:
- 📷 **Interactive Camera Scanner**: Real-time camera stream, rear-camera prioritization, torch/flashlight toggle, and image upload fallback with zero CDN latency.
- 🔗 **Sequential Field Scanning**: Step seamlessly through chained form fields (`->nextField('next_field')`) or via the `QrScanSequence` split-screen container.
- 🔫 **Hardware Scanner (Keyboard Wedge) Support**: Native burst keystroke detection (<50ms) that absorbs trailing `Enter` keys to prevent premature form submissions.
- 📦 **Batch Collector (Repeaters & Lists)**: Continuous scanning mode with duplicate protection and sound/haptic confirmation for rapid inventory/trainee logging.
- 🎨 **Full QR Generator Suite**: Generate SVG & PNG QR codes with captions/text overlays, logo embedding, and schema components for Forms, Tables, Infolists, and Actions.
- 🔊 **Sensory Confirmation**: Instant zero-latency synthesized Web Audio tone and mobile haptic feedback.

---

## Installation

You can install the package via composer:

```bash
composer require mmuqiitf/filament-qr-code
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag="filament-qr-code-config"
```

Register the plugin in your Filament Panel Provider:

```php
use Mmuqiitf\FilamentQrCode\FilamentQrCodePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentQrCodePlugin::make());
}
```

---

## Usage

### 1. Individual QR Scanner Field

Add a QR scanner field to your form schema with camera modal and hardware scanner integration:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;

QrScanner::make('sku')
    ->label('Product SKU / Barcode')
    ->formats([
        BarcodeFormat::QrCode,
        BarcodeFormat::Code128,
        BarcodeFormat::Code39,
        BarcodeFormat::Ean13,
    ])
    ->sound(true)
    ->vibrate(true)
    ->hardwareScanner(enabled: true, burstThresholdMs: 50)
    ->scanFormat(fn ($rawValue) => strtoupper(trim($rawValue)))
    ->onScan(function ($scannedValue, $component) {
        // Custom hook executed on scan
    });
```

### 2. Hands-Free Station Mode (`QrWedgeListener`)

For manufacturing stations and warehouse counters where operators shoot barcodes without touching the mouse:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrWedgeListener;

QrWedgeListener::make([
    'step',
    'employee',
    'document',
    'equipment',
])
    ->autoFocusNext(true)
    ->sound(true);
```
Add `QrWedgeListener` anywhere in your schema. It intercepts hardware scanner bursts across the entire page, populates the active or first empty field, and auto-advances focus.


### 3. Sequential Field Scanning (Chaining)

Auto-focus the next field upon each scan:

```php
QrScanner::make('batch_number')
    ->nextField('serial_number'),

QrScanner::make('serial_number')
    ->nextField('location_code'),

QrScanner::make('location_code'),
```

### 4. Split-Screen Sequence Dashboard Container

Render a unified camera feed and field checklist:

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

QrScanSequence::make([
    'step' => 'Operation Step',
    'employee' => 'Employee ID',
    'document' => 'Document Number',
    'equipment' => 'Equipment Code',
])
    ->fps(15)
    ->qrbox(250)
    ->sound(true)
    ->vibrate(true);
```

### 5. Batch Collector Scanning (Repeaters & Tables)

#### In Form Repeaters:
```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

QrCollector::make('scanned_items')
    ->allowDuplicates(false)
    ->delayBetweenScans(1200);
```

#### As a Table / Repeater Action:
```php
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

$table->headerActions([
    QrCollectAction::make()
        ->allowDuplicates(false),
]);
```

### 6. QR Code Generator Components

#### In Form Schemas:
```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;

QrCodeDisplay::make('qr')
    ->data(fn ($record) => $record?->uuid)
    ->size(200)
    ->color('#1e293b')
    ->caption('Scan to verify')
    ->downloadable();
```

#### In Table Columns:
```php
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

QrColumn::make('sku')
    ->thumbnailSize(48)
    ->modalSize(300)
    ->previewable()
    ->downloadable();
```

#### In Infolists:
```php
use Mmuqiitf\FilamentQrCode\Infolists\Components\QrEntry;

QrEntry::make('verification_code')
    ->size(200)
    ->caption('Official Verification QR');
```

#### As a Download Action:
```php
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;

$table->actions([
    DownloadQrAction::make()
        ->qrData(fn ($record) => $record->verification_url)
        ->qrFileName(fn ($record) => "qr-{$record->id}"),
]);
```

#### Programmatic Standalone Generation:
```php
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

// Generate data URI
$dataUri = FilamentQrCode::make()
    ->format(QrFormat::Svg)
    ->size(300)
    ->generate('https://example.com')
    ->toDataUri();

// Generate PNG with Text Overlay & Download
return FilamentQrCode::make()
    ->withText('BATCH #1024', 16, '#000000')
    ->generate('BATCH-1024')
    ->download('batch-1024');
```

---

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Muhammad Muqiit Faturrahman](https://github.com/mmuqiitf)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
