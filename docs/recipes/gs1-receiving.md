# Recipe: receive a GS1 label

One scan of a GS1-128, GS1 DataMatrix or GS1 QR label fills the GTIN, expiry, batch, serial, net weight and count. The package rejects a label that is not valid GS1 or that lacks a GTIN (01) and an expiry (17). The page then rejects expired stock and a GTIN that is not in the catalogue. `getState()` runs those checks again when the line is saved.

Runs in the example app: `/admin/gs1-receiving` (`app/Filament/Pages/Gs1Receiving.php`). Sample labels to scan from the screen are on `/admin/gs1-labels`. The element-string rules are in [GS1 element strings](../gs1.md).

## The field

```php
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

QrScanner::make('label')
    ->label('GS1 label')
    ->gs1(fill: [
        '01' => 'gtin',
        '10' => 'batch',
        '17' => 'expires_at',
        '21' => 'serial',
        '3103' => 'net_weight_kg',
        '30' => 'quantity',
    ], require: ['01', '17'])
    ->validateUsing(function (string $code): ?string {
        $expiry = Gs1::parse($code)->date('17');

        return $expiry !== null && $expiry->lt(today())
            ? "Expired on {$expiry->format('Y-m-d')}. Quarantine the stock instead of receiving it."
            : null;
    })
    ->validateUsing(function (string $code): ?string {
        $gtin = Gs1::parse($code)->get('01');

        return Product::forGtin($gtin) === null
            ? "GTIN {$gtin} is not in the product catalogue."
            : null;
    })
    ->afterStateUpdated(fn (?string $state, Get $get, Set $set) => $this->showProduct($state, $get, $set))
    ->onRejected(fn (Set $set) => $this->clearLine($set))
    ->hardwareInput(scope: HardwareInputScope::Page)
    ->formats([BarcodeFormat::Code128, BarcodeFormat::DataMatrix, BarcodeFormat::QrCode]);
```

`gs1()` runs before the two closures, so they see the bracketed string. `afterStateUpdated()` shows the product name and, when the label has no count (30), sets the quantity to 1. `onRejected()` clears the line, because a rejection leaves the mapped fields as they were and an earlier label would otherwise stay on screen.

The mapped inputs (`gtin`, `expires_at`, `batch`, `serial`, `net_weight_kg`, `quantity`) are read-only. Quantity stays editable only if you want the receiver to override AI (30); the example keeps it editable and required.

## The page methods

`showProduct()` and `clearLine()` are the callbacks above. `forGtin()` is the catalogue lookup: a GTIN-14 is the stored barcode padded with leading zeros.

```php
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

public static function forGtin(?string $gtin): ?Product
{
    if ($gtin === null || preg_match('/^\d{14}$/', $gtin) !== 1) {
        return null;
    }

    $candidates = [$gtin];

    foreach ([13, 12, 8] as $length) {
        if (str_starts_with($gtin, str_repeat('0', 14 - $length))) {
            $candidates[] = substr($gtin, 14 - $length);
        }
    }

    return Product::query()->whereIn('barcode', $candidates)->first();
}

public function showProduct(?string $state, Get $get, Set $set): void
{
    $set('product', filled($state) ? Product::forGtin(Gs1::parse($state)->get('01'))?->name : null);

    if (blank($get('quantity'))) {
        $set('quantity', 1);
    }
}

public function clearLine(Set $set): void
{
    foreach (['gtin', 'expires_at', 'batch', 'serial', 'net_weight_kg'] as $field) {
        $set($field, null);
    }

    $set('quantity', 1);
    $set('product', null);
}
```

## Saving the line

```php
use Mmuqiitf\FilamentQrCode\Gs1\Gs1;

public function receive(): void
{
    $data = $this->form->getState();
    $product = Product::forGtin(Gs1::parse($data['label'])->get('01'));

    if ($product === null) {
        $this->addError('data.label', 'The label\'s GTIN is not in the product catalogue.');

        return;
    }

    ReceivedLine::create([
        'product_id' => $product->getKey(),
        'label' => $data['label'],
        'gtin' => $data['gtin'],
        'batch' => $data['batch'] ?? null,
        'serial' => $data['serial'] ?? null,
        'expires_at' => $data['expires_at'],
        'net_weight_kg' => $data['net_weight_kg'] ?? null,
        'quantity' => (int) $data['quantity'],
    ]);

    $this->form->fill(['quantity' => 1]);
}
```

Redraw a saved label with `BarcodeColumn::make('label')->type(BarcodeType::Gs1128)`.
