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

    <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 z-20">
        <div class="text-center text-white space-y-2">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-primary-500 border-t-transparent"></div>
            <p class="text-xs">{{ __('filament-qr-code::ui.loading_camera') }}</p>
        </div>
    </div>
</div>
