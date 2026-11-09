# Generating Barcodes

> Back to [README](../README.md) · [Generating QR codes](generating.md) · [Reference](reference.md)

Render 1D barcodes (Code 128, EAN-13, EAN-8, UPC-A, Code 39, ITF-14) in forms, infolists and tables, or generate them in PHP. Encoding comes from [tc-lib-barcode](https://github.com/tecnickcom/tc-lib-barcode); the package draws the bars itself, so the SVG output is stable and accessible. QR codes still use [BaconQR](https://github.com/Bacon/BaconQrCode).

## Supported types

| `BarcodeType` | Accepts | Check digit |
| --- | --- | --- |
| `Code128` (default) | any printable ASCII | built in |
| `Ean13` | 12 or 13 digits | appended when you pass 12, checked when you pass 13 |
| `Ean8` | 7 or 8 digits | appended or checked |
| `UpcA` | 11 or 12 digits | appended or checked |
| `Code39` | `0-9`, `A-Z`, space and `- . $ / + %` (uppercase only) | none |
| `Itf14` | 13 or 14 digits | appended or checked; drawn with bearer bars and its own quiet zone |

That check digit is the GS1 mod-10 digit. Calculate or validate it on its own with [`CheckDigit`](gs1.md#check-digit).

GS1-128 symbols are not drawn yet. Application identifiers can already be read and written with [`Gs1::parse()` and `Gs1::build()`](gs1.md).

## Components

Every component takes `->type(BarcodeType::...)` and encodes its state, or `->data()` when set (a closure receives the record).

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;
use Mmuqiitf\FilamentQrCode\Infolists\Components\BarcodeEntry;
use Mmuqiitf\FilamentQrCode\Tables\Columns\BarcodeColumn;

// Form schema: read-only preview of the field state.
BarcodeDisplay::make('ean')
    ->type(BarcodeType::Ean13);

// Infolist.
BarcodeEntry::make('sku')
    ->type(BarcodeType::Code128)
    ->height(60)
    ->showText(false);

// Table: a compact strip (bar width 1, 32px, no text, no download link).
BarcodeColumn::make('gtin')
    ->type(BarcodeType::Itf14)
    ->data(fn ($record) => $record->case_gtin);
```

| Setter | Default (display / entry) | Default (column) |
| --- | --- | --- |
| `type(BarcodeType\|string)` | `Code128` | `Code128` |
| `format(QrFormat\|string)` | SVG | SVG |
| `barWidth(int $pixels)` | 2 | 1 |
| `height(int $pixels)` | 80 (bars only) | 32 |
| `margin(?int $modules)` | 10, 0 for ITF-14 | same |
| `color()` / `backgroundColor()` | `#000000` / `#ffffff` | same |
| `showText(bool)` | `true` | `false` |
| `downloadable(bool)` | `true` | `false` |

PNG output (`->format('png')`) uses Imagick when it is loaded, otherwise GD, exactly like QR codes.

### Invalid values

A value that doesn't fit the type (wrong length, wrong check digit, lowercase in Code 39) never renders a broken image. The component shows a translated inline message with `role="alert"`, for example *Invalid EAN-13 value: wrong check digit, expected 1.* Validate input up front with `BarcodeService::validate()`, which throws the same `InvalidBarcodeException`.

### Accessibility

The image's alt text names the type and the encoded value, including any appended check digit: `EAN-13 barcode: 4006381333931`. The SVG itself carries `role="img"`, an `aria-label` and a `<title>`, so it stays accessible when you embed the raw markup.

## In PHP

```php
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Exceptions\InvalidBarcodeException;
use Mmuqiitf\FilamentQrCode\Services\BarcodeService;

$barcode = BarcodeService::make()
    ->type(BarcodeType::Ean13)
    ->barWidth(3)
    ->generate('400638133393');

$barcode->getText();       // '4006381333931' (check digit appended)
$barcode->getRaw();        // SVG markup
$barcode->toDataUri();     // data:image/svg+xml;base64,...

return BarcodeService::make()->type('code128')->format('png')->generate($sku)->download('label.png');

try {
    BarcodeService::validate(BarcodeType::UpcA, $input);
} catch (InvalidBarcodeException $e) {
    $e->reason; // 'expected 11 or 12 digits, got 10'
}
```

The `FilamentQrCode` facade starts a barcode too. `FilamentQrCode::barcode()` returns a fresh `BarcodeService`, the same as `BarcodeService::make()`:

```php
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;

$uri = FilamentQrCode::barcode()->type('ean8')->generate('9638507')->toDataUri();
```

Rendered barcodes share the QR render cache (`qr-code.generator.cache_ttl`), and `BarcodeService::flushRenderCache()` clears it.

Barcodes have no signed image route yet: components embed the image as a data URI. A barcode image route is planned for 1.2.
