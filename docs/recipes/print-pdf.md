# Render a label sheet to PDF

The package prints with HTML and CSS. It does not depend on a PDF library. If you need a downloadable PDF, render the same HTML the print page uses with [dompdf](https://github.com/dompdf/dompdf) (directly, or via [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)).

Install one of those packages in the host app. Then rasterize the symbols as PNG — dompdf does not paint SVG reliably — and pass the document HTML in:

```php
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Printing\Label;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheet;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheetRenderer;

$labels = [
    Label::make()->qr('SKU-1')->caption('Widget'),
];

$html = LabelSheetRenderer::make(LabelSheet::avery5160())
    ->format(QrFormat::Png)
    ->toDocument($labels, 'Shelf labels');
```

Feed that `$html` to your PDF engine. With `dompdf/dompdf`, disable remote fetching (`isRemoteEnabled` off — label images are already data URIs), enable the HTML5 parser, `loadHtml($html)`, set the paper to `letter` (or `a4` with `LabelSheet::l7160()`), then `render()` and `stream('labels.pdf')`. With `barryvdh/laravel-dompdf` the same HTML is loaded, given the same paper size, and downloaded as `labels.pdf`.
