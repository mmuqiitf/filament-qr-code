<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\View\View;
use Mmuqiitf\FilamentQrCode\Concerns\HasFeedback;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;

class QrCollectAction extends Action
{
    use HasFeedback;
    use HasHardwareScanner;

    protected bool|Closure $allowDuplicates = false;

    protected int|Closure $fps = 15;

    protected int|Closure $qrbox = 250;

    protected ?Closure $onItemScanned = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('qr_collect');
        $this->label(__('Batch Scan QR Codes'));
        $this->icon('heroicon-o-qr-code');
        $this->color('primary');

        $this->modalHeading(__('Batch QR Scanner'));
        $this->modalDescription(__('Continuously scan QR codes to add items.'));
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel(__('Done'));

        $this->modalContent(function (): View {
            /** @var view-string $viewName */
            $viewName = 'filament-qr-code::components.qr-collector-modal';

            return view($viewName, [
                'allowDuplicates' => $this->isDuplicatesAllowed(),
                'sound' => $this->hasSound(),
                'vibrate' => $this->hasVibration(),
                'hardwareScanner' => $this->isHardwareScannerEnabled(),
                'fps' => (int) $this->evaluate($this->fps),
                'qrbox' => (int) $this->evaluate($this->qrbox),
            ]);
        });
    }

    public function allowDuplicates(bool|Closure $condition = true): static
    {
        $this->allowDuplicates = $condition;

        return $this;
    }

    public function onScan(?Closure $callback): static
    {
        $this->onItemScanned = $callback;

        return $this;
    }

    public function isDuplicatesAllowed(): bool
    {
        return (bool) $this->evaluate($this->allowDuplicates);
    }
}
