<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Mmuqiitf\FilamentQrCode\Concerns\HasQrScanner;
use Mmuqiitf\FilamentQrCode\Concerns\InteractsWithScanner;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;

abstract class BaseScannerPage extends Page
{
    use HasQrScanner;
    use InteractsWithScanner;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-qr-code';

    protected string $view = 'filament-qr-code::pages.qr-code-scanner-page';

    /**
     * Camera management properties
     */
    public array $availableCameras = [];

    public ?string $selectedCameraId = null;

    public int $fps = 30;

    public bool $showCameraSelector = true;

    public bool $showFpsControl = true;

    public function mount(): void
    {
        $this->mountHasQrScanner();
        $this->scanMode = ScanMode::Single;

        // Initialize camera management
        $this->fps = $this->getDefaultFps();
        $this->showCameraSelector = $this->shouldShowCameraSelector();
        $this->showFpsControl = $this->shouldShowFpsControl();
    }

    /**
     * Define the scan fields for this scanner page.
     * Users should override this method to customize their scan fields.
     */
    abstract public function getScanFields(): array;

    /**
     * Get the default navigation label.
     * Users can override this method to customize the navigation label.
     */
    public static function getNavigationLabel(): string
    {
        return __('filament-qr-code::messages.scan_qr_code');
    }

    /**
     * Get the page title.
     * Users can override this method to customize the page title.
     */
    public function getTitle(): string
    {
        return static::getNavigationLabel();
    }

    /**
     * Get the header actions for the scanner page.
     * Users can override this method to add custom actions.
     */
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('switchCamera')
                ->label(__('filament-qr-code::messages.switch_camera'))
                ->icon('heroicon-o-camera')
                ->action('switchCamera'),
            \Filament\Actions\Action::make('reset')
                ->label(__('filament-qr-code::messages.reset'))
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->action('resetScanSequence'),
        ];
    }
}
