# Generating QR Codes

> Back to [README](../README.md) · [Scanning](scanning.md) · [Batch collecting](collecting.md) · [Reference](reference.md)

Render QR codes in forms, tables, and infolists, offer downloads and bulk ZIP exports, or generate standalone via the facade.

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

Thumbnails and modal previews share a capped in-process render cache plus a persistent Laravel-cache L2 (`generator.cache_ttl`, default 86400s; `QrCodeService::persistentCache(false)` / `::cacheStore('redis')` to tune). The modal image loads lazily through a signed `filament-qr-code.image` route, so a 25-row table encodes 25 thumbnails instead of 50 mixed-size images — `->lazyModal(false)` restores eager data-URIs.

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
        ->qrFileName(fn ($record) => "qr-{$record->id}")
        ->qrFormat(QrFormat::Png)
        ->qrImageSize(400)
        ->qrMargin(2),
]);
```

#### As a Bulk ZIP Export:

```php
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;

$table->bulkActions([
    DownloadQrBulkAction::make()
        ->qrData('sku') // attribute name, or fn ($record) => ...
        ->qrFileName(fn ($record) => $record->sku)
        ->qrFormat(QrFormat::Png)
        ->zipName('shelf-labels.zip'),
]);
```

Rows without data are skipped; the archive downloads as one ZIP (needs the PHP `zip` extension). For printed shelf labels, wrap any QR images in `.filament-qr-label-sheet` / `.filament-qr-label` — the print stylesheet hides camera UI and tiles three labels per row.

#### Programmatic Standalone Generation:

```php
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;

// Generate data URI
$dataUri = FilamentQrCode::make()
    ->format(QrFormat::Svg)
    ->size(300)
    ->margin(2)
    ->color('#0f172a')
    ->backgroundColor('#ffffff')
    ->errorCorrection('M') // L, M, Q, H
    ->generate('https://example.com')
    ->toDataUri();

// Generate PNG with Text Overlay & Download
return FilamentQrCode::make()
    ->withText('BATCH #1024', 16, '#000000')
    ->generate('BATCH-1024')
    ->download('batch-1024'); // StreamedResponse download; stream() inlines
```

Rules that bite:

- `withText()` and `logo()` need raster output, so the service always returns PNG when either is set — regardless of whether `format()` was called before or after. Request PNG explicitly when overlaying.
- Invalid hex colors throw `InvalidArgumentException` instead of silently rendering black.
- `logo($path, $size)` forces error-correction level H for scannability.
- Identical payloads share the render cache, so tables with repeated values encode once.

#### Common payloads without hand-escaping

```php
use Mmuqiitf\FilamentQrCode\Support\QrPayload;

QrPayload::wifi('Shop Floor', 'secret-1');                    // WPA (nopass + hidden supported)
QrPayload::vcard(['firstName' => 'Siti', 'phone' => '...']);   // escaped vCard 3.0
QrPayload::mailto('ops@example.com', 'Stock alert', '...');   // mailto with subject/body
QrPayload::sms('+621234567', 'Arrived');                     // SMSTO
QrPayload::geo(-6.2, 106.8, 'Warehouse 7');                   // geo with query
```
