# Recipe: POS checkout with continuous scanning

A cashier page where a handheld scanner (keyboard wedge) or the camera adds products to a cart, one line per SKU, without touching the mouse.

## The form

```php
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

QrScanner::make('scan')
    ->label('Scan item')
    ->mode(ScanMode::Continuous)
    ->hardwareInput(scope: HardwareInputScope::Page)
    ->lookup(Product::class, 'sku')
    ->onScanned(function (Product $record, Get $get, Set $set): void {
        $set('lines', CartLines::add($get('lines') ?? [], $record));
    })
    ->dehydrated(false),

Repeater::make('lines')
    ->schema([
        TextInput::make('sku')->disabled(),
        TextInput::make('name')->disabled(),
        TextInput::make('quantity')->numeric()->minValue(1),
    ])
    ->addable(false),
```

## Merging lines

The package calls `onScanned()` once per accepted code; merging is the app's job:

```php
final class CartLines
{
    /** @param array<int|string, array{sku: string, name: string, quantity: int}> $lines */
    public static function add(array $lines, Product $product): array
    {
        foreach ($lines as $key => $line) {
            if ($line['sku'] === $product->sku) {
                $lines[$key]['quantity']++;

                return $lines;
            }
        }

        $lines[] = ['sku' => $product->sku, 'name' => $product->name, 'quantity' => 1];

        return $lines;
    }
}
```

## How it behaves

- **Page scope**: with nothing focused, a wedge scan goes to the first listening field on the page, here the scan field. A focused scan field always wins, so another scanner on the page still works when the cashier clicks into it.
- **Empty after each scan** (`continuousDisplay`, default `Empty`): the field is ready for the next item.
- **Unknown code**: the lookup rejects it, the error tone plays and the message shows under the field. Scanning continues (`pauseOnReject()` to stop until the cashier confirms).
- **Camera**: closes after each scan by default; `keepCameraOpen()` for scan-scan-scan with a phone.
- **Same item twice**: two lines merge into quantity 2. Scans of the same code closer than 300 ms are treated as a camera re-read and ignored (`qr-code.scan.continuous.cooldown_ms`).
- **Fast bursts**: one scan is queued while the previous one is checked; more fire `qr-scan-dropped`.

Defaults for all of this live in `config/qr-code.php` under `scan.continuous`.
