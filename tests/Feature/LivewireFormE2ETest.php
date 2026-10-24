<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\TestLivewireFormComponent;

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
