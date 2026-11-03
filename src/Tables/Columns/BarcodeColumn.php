<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Columns;

use Filament\Tables\Columns\Column;
use Mmuqiitf\FilamentQrCode\Concerns\HasBarcodeOptions;

/**
 * Table column that shows a compact 1D barcode of its state or data():
 * bar width 1, 32px high and no text line unless configured.
 */
class BarcodeColumn extends Column
{
    use HasBarcodeOptions;

    protected string $view = 'filament-qr-code::components.barcode-column';

    protected function getDefaultBarWidth(): int
    {
        return 1;
    }

    protected function getDefaultBarHeight(): int
    {
        return 32;
    }

    protected function getDefaultShowText(): bool
    {
        return false;
    }

    protected function getDefaultDownloadable(): bool
    {
        return false;
    }
}
