<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrHardwareScannerListener;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

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

it('renders QrScanner form fields in Livewire end-to-end', function () {
    Livewire::test(TestLivewireFormComponent::class)
        ->assertSuccessful()
        ->assertSee('qrScanner')
        ->assertSee('qrScanSequence')
        ->assertSee('qrCollector')
        ->assertSee('PREVIEW-QR-123')
        ->assertSeeHtml('data-field-name="step"')
        ->assertSeeHtml('data-field-name="employee"');
});

it('labels the scanner modal for assistive tech', function () {
    Livewire::test(TestLivewireFormComponent::class)
        ->assertSuccessful()
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('aria-hidden="true"');
});

it('validates and submits scanned values end-to-end in Livewire form', function () {
    Livewire::test(TestLivewireFormComponent::class)
        // Try submitting empty required fields -> errors
        ->call('submit')
        ->assertHasErrors(['data.step', 'data.employee'])
        // Simulate hardware scanner / camera scanning filling the fields
        ->set('data.step', 'STEP-001')
        ->set('data.employee', 'EMP-4421')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSet('data.step', 'STEP-001')
        ->assertSet('data.employee', 'EMP-4421');
});
