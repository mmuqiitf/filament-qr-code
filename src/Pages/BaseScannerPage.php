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

    /**
     * Handle camera selection change from UI.
     */
    public function selectCamera(string $cameraId): void
    {
        $this->selectedCameraId = $cameraId;
        
        // Dispatch event to JavaScript to switch camera
        $this->dispatch('camera-changed', cameraId: $cameraId);
    }

    /**
     * Handle FPS update from UI with validation.
     */
    public function updateFps(int $fps): void
    {
        // Validate FPS is within acceptable range
        if ($fps < 5 || $fps > 60) {
            // Revert to previous valid value
            $this->fps = $this->fps;
            return;
        }

        $this->fps = $fps;
        
        // Dispatch event to JavaScript to update FPS
        $this->dispatch('fps-changed', fps: $fps);
    }

    /**
     * Handle cameras detected event from JavaScript.
     */
    #[On('cameras-detected')]
    public function handleCamerasDetected(array $cameras): void
    {
        $this->availableCameras = $cameras;
        
        // Set initial selected camera if not already set
        if (empty($this->selectedCameraId) && ! empty($cameras)) {
            $defaultFacing = $this->getDefaultCameraFacing();
            
            // Try to find a camera matching the default facing
            foreach ($cameras as $camera) {
                $label = strtolower($camera['label'] ?? '');
                
                if ($defaultFacing === 'environment' && 
                    (str_contains($label, 'back') || str_contains($label, 'rear') || str_contains($label, 'environment'))) {
                    $this->selectedCameraId = $camera['id'];
                    break;
                } elseif ($defaultFacing === 'user' && 
                    (str_contains($label, 'front') || str_contains($label, 'user') || str_contains($label, 'face'))) {
                    $this->selectedCameraId = $camera['id'];
                    break;
                }
            }
            
            // Fallback to first camera if no match found
            if (empty($this->selectedCameraId)) {
                $this->selectedCameraId = $cameras[0]['id'] ?? null;
            }
        }
    }

    /**
     * Get camera options for UI dropdown.
     */
    public function getCameraOptions(): array
    {
        $options = [];
        
        foreach ($this->availableCameras as $camera) {
            $options[$camera['id']] = $camera['label'];
        }
        
        return $options;
    }

    /**
     * Get the default FPS from configuration.
     * Users can override this method to provide custom default FPS.
     */
    protected function getDefaultFps(): int
    {
        return config('qr-code.default_fps', 30);
    }

    /**
     * Get the default camera facing from configuration.
     * Users can override this method to provide custom default camera facing.
     */
    protected function getDefaultCameraFacing(): string
    {
        return config('qr-code.default_camera_facing', 'environment');
    }

    /**
     * Determine if the camera selector should be shown.
     * Users can override this method to customize visibility.
     */
    protected function shouldShowCameraSelector(): bool
    {
        return config('qr-code.show_camera_selector', true);
    }

    /**
     * Determine if the FPS control should be shown.
     * Users can override this method to customize visibility.
     */
    protected function shouldShowFpsControl(): bool
    {
        return config('qr-code.show_fps_control', true);
    }
}
