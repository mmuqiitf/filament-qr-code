<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Livewire\Attributes\On;
use Mmuqiitf\FilamentQrCode\Enums\CameraFacing;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;

trait HasQrScanner
{
    public ?string $currentScanField = null;

    public array $scanResults = [];

    public bool $isScannerActive = false;

    public CameraFacing $cameraFacing = CameraFacing::Back;

    public ScanMode $scanMode = ScanMode::Single;

    public function mountHasQrScanner(): void
    {
        $this->scanResults = [];
        $this->currentScanField = $this->getDefaultScanField();
        $this->cameraFacing = CameraFacing::tryFrom(config('qr-code.camera_facing', 'environment')) ?? CameraFacing::Back;
        $this->scanMode = ScanMode::tryFrom(config('qr-code.scan_mode', 'single')) ?? ScanMode::Single;
    }

    /**
     * Override in your page to define the scan fields and their order
     *
     * @return array<int, array{key: string, label: string}>
     */
    public function getScanFields(): array
    {
        return [
            // ['key' => 'field_name', 'label' => 'Field Label'],
        ];
    }

    public function getDefaultScanField(): ?string
    {
        $fields = $this->getScanFields();

        return $fields[0]['key'] ?? null;
    }

    #[On('qr-code-scanned')]
    public function handleQrCodeScanned(string $value, ?string $field = null): void
    {
        $targetField = $field ?? $this->currentScanField;

        if (! $targetField) {
            return;
        }

        $this->scanResults[$targetField] = $value;

        // Update form data if using Filament forms
        if (property_exists($this, 'data') && is_array($this->data)) {
            data_set($this->data, $targetField, $value);
        }

        // Fire event for child classes to hook into
        $this->afterQrCodeScanned($value, $targetField);

        // Move to next field if in sequence mode
        if ($this->scanMode === ScanMode::Sequence) {
            $this->moveToNextScanField();
        }
    }

    /**
     * Hook for child classes to perform additional logic after scan
     */
    protected function afterQrCodeScanned(string $value, string $field): void
    {
        // Override in child class
    }

    public function setCurrentScanField(string $field): void
    {
        $this->currentScanField = $field;
    }

    public function moveToNextScanField(): void
    {
        $fields = $this->getScanFields();
        $currentIndex = collect($fields)->search(fn ($f) => $f['key'] === $this->currentScanField);

        if ($currentIndex !== false && $currentIndex < count($fields) - 1) {
            $this->currentScanField = $fields[$currentIndex + 1]['key'];
            $this->dispatch('scanner-field-changed', field: $this->currentScanField);
        }
    }

    public function resetScanSequence(): void
    {
        $this->scanResults = [];
        $this->currentScanField = $this->getDefaultScanField();
        $this->dispatch('scanner-reset-required');
    }

    public function clearScanResult(string $field): void
    {
        unset($this->scanResults[$field]);

        if (property_exists($this, 'data') && is_array($this->data)) {
            data_set($this->data, $field, null);
        }
    }

    public function startScanner(): void
    {
        $this->isScannerActive = true;
    }

    public function stopScanner(): void
    {
        $this->isScannerActive = false;
    }

    public function toggleScanner(): void
    {
        $this->isScannerActive = ! $this->isScannerActive;
    }

    public function switchCamera(): void
    {
        $this->cameraFacing = match ($this->cameraFacing) {
            CameraFacing::Front => CameraFacing::Back,
            CameraFacing::Back => CameraFacing::Front,
            CameraFacing::Auto => CameraFacing::Back,
        };

        $this->dispatch('camera-switch-required', facing: $this->cameraFacing->value);
    }
}
