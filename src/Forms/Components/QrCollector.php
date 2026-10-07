<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasCameraScanning;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Validation\DistinctCodes;

class QrCollector extends Field
{
    use HasCameraScanning;
    use HasFeedback;
    use HasHardwareScanner;

    protected string $view = 'filament-qr-code::components.qr-collector';

    protected bool|Closure $allowDuplicates = false;

    protected int|Closure $delayBetweenScansMs = 1200;

    public function allowDuplicates(bool|Closure $condition = true): static
    {
        $this->allowDuplicates = $condition;

        return $this;
    }

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
        if (! (bool) $this->evaluate($condition)) {
            return $this;
        }

        $this->rules(['array', new DistinctCodes]);

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
}
