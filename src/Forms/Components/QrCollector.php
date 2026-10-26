<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Concerns\HasRenderHookScopes;
use Mmuqiitf\FilamentQrCode\Concerns\HasScanPayload;
use Mmuqiitf\FilamentQrCode\Support\ScanMessages;
use Mmuqiitf\FilamentQrCode\Validation\DistinctCodes;

class QrCollector extends Field
{
    use HasCameraScanning;
    use HasFeedback;
    use HasHardwareScanner;
    use HasRenderHookScopes;
    use HasScanPayload;

    protected string $view = 'filament-qr-code::components.qr-collector';

    protected bool|Closure $allowDuplicates = false;

    protected int|Closure $delayBetweenScansMs = 1200;

    /** Accept the same code more than once in the list (browser-side check). */
    public function allowDuplicates(bool|Closure $condition = true): static
    {
        $this->allowDuplicates = $condition;

        return $this;
    }

    /** Pause in milliseconds before the next camera scan is accepted. */
    public function delayBetweenScans(int|Closure $ms): static
    {
        $this->delayBetweenScansMs = $ms;

        return $this;
    }

    /**
     * Server-side duplicate guard to match the client-side
     * allowDuplicates(false): identical codes fail submit-time validation.
     */
    public function distinctItems(bool|Closure $condition = true): static
    {
        $this->rule('array', $condition);
        $this->rule(new DistinctCodes, $condition);

        return $this;
    }

    public function isDuplicatesAllowed(): bool
    {
        return (bool) $this->evaluate($this->allowDuplicates);
    }

    public function getDelayBetweenScansMs(): int
    {
        return (int) $this->evaluate($this->delayBetweenScansMs);
    }

    /** @return array<string, mixed> */
    public function getComponentScanPayload(): array
    {
        return [
            'allowDuplicates' => $this->isDuplicatesAllowed(),
            'delayBetweenScansMs' => $this->getDelayBetweenScansMs(),
            // $wire proxies every name, so the browser cannot probe for the hook itself.
            'wireScanHandler' => isset($this->livewire) && method_exists($this->livewire, 'handleCollectorScan'),
            ...ScanMessages::forAnnouncements(),
        ];
    }
}
