<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrScanner;
use Mmuqiitf\FilamentQrCode\Concerns\InteractsWithScanner;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;

class QrCodeScannerPage extends Page
{
    use HasQrScanner;
    use InteractsWithScanner;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static string $view = 'filament-qr-code::pages.qr-code-scanner-page';

    protected static ?string $slug = 'qr-scanner';

    public static function getNavigationLabel(): string
    {
        return __('filament-qr-code::messages.scan_qr_code');
    }

    public function getTitle(): string
    {
        return __('filament-qr-code::messages.scan_qr_code');
    }

    public function mount(): void
    {
        $this->mountHasQrScanner();
        $this->scanMode = ScanMode::Single;
    }

    public function getScanFields(): array
    {
        return [
            ['key' => 'scanned_value', 'label' => __('filament-qr-code::messages.scanned_value')],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('switchCamera')
                ->label(__('filament-qr-code::messages.switch_camera'))
                ->icon('heroicon-o-camera')
                ->action('switchCamera'),
            Action::make('reset')
                ->label(__('filament-qr-code::messages.reset'))
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->action('resetScanSequence'),
        ];
    }
}
