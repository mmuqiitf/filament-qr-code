# Recipe: goods receiving with a handheld scanner

A receiving desk scans a purchase order once, then for every item a SKU, an optional lot and one serial per unit. A keyboard-wedge scanner drives it without focusing anything; the item is saved as soon as it's complete.

Runs in the example app: `/admin/goods-receiving` (`app/Filament/Pages/GoodsReceiving.php`).

## The sequence

```php
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

QrScanSequence::make()
    ->statePath('receipt')
    ->steps([
        ScanStep::make('po')
            ->label('Purchase order')
            ->validateUsing(['regex:/^PO-\d+$/'])
            ->sticky(),                                   // kept for every item on the PO
        ScanStep::make('sku')
            ->label('SKU')
            ->lookup(Product::class, 'sku'),
        ScanStep::make('lot')
            ->label('Lot (if labelled)')
            ->validateUsing(['regex:/^LOT-[A-Z0-9]+$/'])
            ->optional(),                                 // a serial scanned here gives way to the serials step
        ScanStep::make('serials')
            ->label('Serial numbers')
            ->validateUsing(['regex:/^SN-[A-Z0-9-]+$/'])
            ->repeat(fn (): int => max(1, $this->quantity)), // follows a page field; the k / n counter updates live
    ])
    ->validateAllUsing(function (array $values): array {
        $prefix = 'SN-'.($values['sku'] ?? '').'-';

        foreach ($values['serials'] ?? [] as $serial) {
            if (! str_starts_with((string) $serial, $prefix)) {
                return ['serials' => "Serials must belong to {$values['sku']}."];
            }
        }

        return [];
    })
    ->onStepRejected(fn (string $step, ScanResult $result) => logger()->notice("Receiving: {$step} rejected {$result->code}", ['message' => $result->message]))
    ->autoSubmit(fn (): bool => $this->autoSubmit)
    ->resetAfterSubmit()
    ->editable(false),
```

## Saving the item

`autoSubmit()` calls the page's `submit()` once the item is complete. `getState()` re-runs every step check and `validateAllUsing()`, so a typed or tampered value can't slip through:

```php
public function submit(): void
{
    $receipt = $this->form->getState()['receipt'] ?? [];
    $product = Product::query()->where('sku', $receipt['sku'])->firstOrFail();

    GoodsReceipt::create([
        'po_number' => $receipt['po'],
        'product_id' => $product->getKey(),
        'lot_number' => $receipt['lot'] ?? null,
        'serials' => array_values($receipt['serials']),
    ]);

    // Next item: keep the sticky PO. (resetAfterSubmit() does this for auto-submits;
    // a manual "Receive item" button needs it here.)
    $this->sequence()->resetSequence();
}

protected function sequence(): QrScanSequence
{
    $sequence = $this->form->getComponent(fn ($component): bool => $component instanceof QrScanSequence);

    if (! $sequence instanceof QrScanSequence) {
        throw new LogicException('The form has no QrScanSequence.');
    }

    return $sequence;
}
```

## How it behaves

- **Order** `po, sku, lot?, serials`: on an item without a lot label, the operator scans the first serial at the lot step. It fails the lot's rule but fits the serials step, so the lot is skipped (`skipped: ['lot']`) and the serial lands there. Typed corrections never skip.
- **Sticky PO**: stays across items. The step's **Clear** button (or scanning it again after selecting it) changes it.
- **Serials**: duplicates are rejected; each code has a remove button for mis-scans. The count follows `$this->quantity` on every re-render, with no re-mount.
- **Whole item**: `validateAllUsing()` runs when the last serial is in. On failure the sequence jumps back to the failing step (`onInvalidSequence()`, default `JumpToStep`).
- **Side effects**: `onStepRejected()` (and `onStepAccepted()`) are for logging and notifications only. Their return value is ignored and an exception inside is reported without changing the scan.
- **Events**: listen to `ScanRejected` / `ScanAccepted` instead when the audit lives elsewhere. Step events carry `statePath` (`data.receipt.serials`), `sequence` and `values`.
