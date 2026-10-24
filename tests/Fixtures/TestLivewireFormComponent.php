<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Livewire\Component;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

/**
 * Shared by LivewireFormE2ETest and ScanPayloadRenderTest. It lives in its own
 * PSR-4 file so it autoloads whichever test file runs first.
 */
class TestLivewireFormComponent extends Component implements HasForms
{
    use InteractsWithForms;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public bool $submitted = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form|Schema $form): Form|Schema
    {
        return $form
            ->schema([
                QrHardwareScannerListener::make(['step', 'employee']),
                QrScanner::make('step')
                    ->formats([BarcodeFormat::Code128, BarcodeFormat::QrCode])
                    ->nextField('employee')
                    ->required(),
                QrScanner::make('employee')
                    ->required(),
                QrScanSequence::make([
                    'part_a' => 'Part A',
                    'part_b' => 'Part B',
                ]),
                QrCollector::make('scanned_items'),
                QrCodeDisplay::make('generated_qr')
                    ->data('PREVIEW-QR-123')
                    ->caption('PREVIEW-QR-123'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $this->submitted = true;
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>
            <form wire:submit="submit">
                {{ $this->form }}
                <button type="submit" id="submit-button">Submit</button>
            </form>
        </div>
        BLADE;
    }
}
