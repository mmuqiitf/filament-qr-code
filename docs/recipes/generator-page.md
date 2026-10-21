# Recipe: a label generator page

A Filament page that prints QR labels for your own records (products, work orders, badges) and downloads single codes as SVG or PNG, built only on the generator API. Use it when the built-in `QrCodeColumn` / `QrCodeEntry` don't fit, for example a printable sheet.

Runs in the example app: `/admin/qr-labels` (`app/Filament/Pages/QrLabels.php`).

## Building the codes

```php
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Facades\FilamentQrCode;

/** @return list<array{code: string, caption: string}> */
public function labels(): array
{
    return array_values(match ($this->data['set'] ?? 'products') {
        'products' => Product::query()->orderBy('sku')->get()->map(fn (Product $product): array => ['code' => $product->sku, 'caption' => $product->name])->all(),
        'badges' => array_map(fn (array $badge): array => ['code' => $badge['badge'], 'caption' => $badge['name']], app(BadgeDirectory::class)->active()),
        default => [],
    });
}

public function previewDataUri(string $code): string
{
    return FilamentQrCode::make()
        ->format(QrFormat::Svg)               // SVG: crisp on screen and paper, no image extension needed
        ->size((int) ($this->data['size'] ?? 160))
        ->margin(1)
        ->color($this->validColor())
        ->generate($code)
        ->toDataUri();
}

public function download(string $code): ?StreamedResponse
{
    // Only codes on the current sheet: the method is callable from the browser.
    if (! in_array($code, array_column($this->labels(), 'code'), true)) {
        return null;
    }

    return FilamentQrCode::make()
        ->format(($this->data['format'] ?? 'svg') === 'png' ? QrFormat::Png : QrFormat::Svg)
        ->size(400)
        ->margin(2)
        ->color($this->validColor())
        ->errorCorrection('M')
        ->generate($code)
        ->download('qr-'.$code);              // the extension follows the format
}

protected function validColor(): string
{
    $color = (string) ($this->data['color'] ?? '#000000');

    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) === 1 ? $color : '#000000';
}
```

## The sheet

```blade
<div class="filament-qr-label-sheet">
    @foreach ($this->labels() as $label)
        <figure class="filament-qr-label">
            <img src="{{ $this->previewDataUri($label['code']) }}" alt="{{ $label['code'] }}">
            <figcaption>{{ $label['code'] }} · {{ $label['caption'] }}</figcaption>
            <x-filament::link class="filament-qr-no-print" wire:click="download(@js($label['code']))">Download</x-filament::link>
        </figure>
    @endforeach
</div>
<x-filament::button class="filament-qr-no-print" x-on:click="window.print()">Print</x-filament::button>
```

The package's print stylesheet lays out `.filament-qr-label-sheet` / `.filament-qr-label` as a grid and hides `.filament-qr-no-print` when printing.

## Notes

- PNG needs the `imagick` extension; `php artisan qr-code:doctor` tells you whether it's there. SVG has no requirements.
- Validate user-chosen colours and sizes before passing them on, as above.
- Generated codes aren't stored; for many labels, cache `previewDataUri()` per code and style.
- To print what a scanner reads, keep the label value identical to what your `lookup()` column holds (or what `normalizeWith()` produces).
