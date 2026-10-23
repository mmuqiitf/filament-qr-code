{{-- Torch / zoom: rendered inside a viewfinder, visible only when the running camera reports the capability --}}
<div
    data-qr-part="camera.controls"
    x-show="isScanning && (cameraCaps.torch || cameraCaps.zoom)"
    x-cloak
    class="fi-qr-camera-controls absolute bottom-2 inset-x-2 z-10 flex items-center gap-2 rounded-md bg-gray-950/60 px-2 py-1 text-white"
>
    <button
        type="button"
        x-show="cameraCaps.torch"
        @click="toggleTorch()"
        :aria-pressed="torchOn ? 'true' : 'false'"
        aria-label="{{ __('filament-qr-code::ui.torch') }}"
        title="{{ __('filament-qr-code::ui.torch') }}"
        class="fi-qr-camera-torch rounded p-1 hover:bg-white/10"
        :class="torchOn ? 'text-warning-400' : 'text-white'"
    >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
    </button>

    <template x-if="cameraCaps.zoom">
        <input
            type="range"
            class="fi-qr-camera-zoom w-full"
            aria-label="{{ __('filament-qr-code::ui.zoom') }}"
            :min="cameraCaps.zoom.min"
            :max="cameraCaps.zoom.max"
            :step="cameraCaps.zoom.step"
            :value="zoomLevel"
            @input="setZoom($event.target.value)"
        >
    </template>
</div>
