<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

trait InteractsWithScanner
{
    /**
     * Get the scanner configuration for the current context
     *
     * @return array<string, mixed>
     */
    public function getScannerConfiguration(): array
    {
        return [
            'cameraFacing' => $this->cameraFacing ?? config('qr-code.camera_facing', 'environment'),
            'scanMode' => $this->scanMode ?? config('qr-code.scan_mode', 'single'),
            'scanDelay' => config('qr-code.scan_delay', 1500),
            'fps' => config('qr-code.fps', 30),
            'qrboxSize' => config('qr-code.qrbox_size', 250),
            'showPreview' => config('qr-code.show_preview', true),
            'beepOnScan' => config('qr-code.beep_on_scan', true),
            'vibrateOnScan' => config('qr-code.vibrate_on_scan', true),
        ];
    }

    /**
     * Get the view data for an embedded scanner component
     *
     * @return array<string, mixed>
     */
    public function getEmbeddedScannerViewData(): array
    {
        $config = $this->getScannerConfiguration();

        return array_merge($config, [
            'statePath' => $this->currentScanField ?? null,
            'embedded' => true,
        ]);
    }
}
