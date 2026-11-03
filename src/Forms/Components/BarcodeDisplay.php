<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasBarcodeOptions;

/** Read-only form field that shows a 1D barcode of its state or data(). */
class BarcodeDisplay extends Field
{
    use HasBarcodeOptions;

    protected string $view = 'filament-qr-code::components.barcode-display';
}
