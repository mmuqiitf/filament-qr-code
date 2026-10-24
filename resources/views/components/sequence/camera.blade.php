<div data-qr-part="sequence.camera" class="fi-qr-sequence-camera filament-qr-viewfinder">
    <div :id="elementId" class="w-full h-full"></div>

    <div x-show="isScanning" class="filament-qr-reticle" aria-hidden="true">
        <div class="filament-qr-reticle-box">
            <div class="filament-qr-reticle-corner top-left"></div>
            <div class="filament-qr-reticle-corner top-right"></div>
            <div class="filament-qr-reticle-corner bottom-left"></div>
            <div class="filament-qr-reticle-corner bottom-right"></div>
            <div class="filament-qr-laser"></div>
        </div>
    </div>

    @include('filament-qr-code::components.camera-status', ['retry' => 'retryCamera()'])
</div>
