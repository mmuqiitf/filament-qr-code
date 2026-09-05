<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;

class QrCollector extends Field
{
    use HasFeedback;
    use HasHardwareScanner;

    protected string $view = 'filament-qr-code::components.qr-collector';

    protected bool|Closure $allowDuplicates = false;

    protected int|Closure $delayBetweenScansMs = 1200;

    protected int|Closure $fps = 15;

    protected int|Closure $qrbox = 250;

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

    public function fps(int|Closure $fps): static
    {
        $this->fps = $fps;

        return $this;
    }

    public function qrbox(int|Closure $qrbox): static
    {
        $this->qrbox = $qrbox;

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

    public function getFps(): int
    {
        return (int) $this->evaluate($this->fps);
    }

    public function getQrbox(): int
    {
        return (int) $this->evaluate($this->qrbox);
    }
}
