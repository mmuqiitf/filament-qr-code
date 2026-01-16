<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Pages;

class QrCodeScannerPage extends BaseScannerPage
{
    protected static ?string $slug = 'qr-scanner';

    public function getScanFields(): array
    {
        return [
            ['key' => 'scanned_value', 'label' => __('filament-qr-code::messages.scanned_value')],
        ];
    }
}
