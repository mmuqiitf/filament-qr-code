<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Livewire\Livewire;

it('renders one scan payload with canonical keys on every scan surface', function () {
    Livewire::test(TestLivewireFormComponent::class)
        ->assertSuccessful()
        // Shared feedback + hardware keys, shipped once per surface.
        ->assertSeeHtml('beepDurationMs')
        ->assertSeeHtml('vibrateDurationMs')
        ->assertSeeHtml('beepFrequency')
        ->assertSeeHtml('burstThresholdMs')
        ->assertSeeHtml('minBarcodeLength')
        ->assertSeeHtml('scanTimeoutMs')
        ->assertSeeHtml('suppressWhenGlobalListenerActive')
        // Camera keys on the camera surfaces.
        ->assertSeeHtml('preferRearCamera')
        ->assertSeeHtml('insecureMessage')
        // Surface-specific extras.
        ->assertSeeHtml('nextField')
        ->assertSeeHtml('statePrefix')
        ->assertSeeHtml('allowDuplicates')
        ->assertSeeHtml('delayBetweenScansMs')
        // Station Listener extras.
        ->assertSeeHtml('preventSubmit')
        ->assertSeeHtml('autoFocusNext');
});

it('renders the configured scan values into the payload, not hardcoded defaults', function () {
    config()->set('qr-code.hardware_scanner.burst_threshold_ms', 75);

    Livewire::test(TestLivewireFormComponent::class)
        ->assertSuccessful()
        ->assertSeeHtml('burstThresholdMs')
        ->assertSee('75');
});
