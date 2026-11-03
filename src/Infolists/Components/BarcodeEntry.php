<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Mmuqiitf\FilamentQrCode\Concerns\HasBarcodeOptions;

/** Infolist entry that shows a 1D barcode of its state or data(). */
class BarcodeEntry extends Entry
{
    use HasBarcodeOptions;

    protected string $view = 'filament-qr-code::components.barcode-entry';
}
