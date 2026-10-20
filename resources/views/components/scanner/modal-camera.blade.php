{{-- Camera device selector (implicit label associates text without duplicating ids) --}}
<div data-qr-part="scanner.modal-camera" x-show="devices.length > 1" class="fi-qr-scanner-modal-camera flex items-center gap-2 text-xs">
    <label class="flex items-center gap-2 text-gray-500 dark:text-gray-400 shrink-0 w-full">{{ __('filament-qr-code::ui.camera') }}
    <select
        x-model="selectedDeviceId"
        @change="startScan()"
        class="fi-select-input w-full rounded-md border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs text-gray-900 dark:text-white py-1 px-2"
    >
        <template x-for="dev in devices" :key="dev.id">
            <option :value="dev.id" x-text="dev.label || ('Camera ' + dev.id)"></option>
        </template>
    </select>
    </label>
</div>

{{-- Viewfinder --}}
<div class="filament-qr-viewfinder">
    <div :id="scannerElementId" class="w-full h-full"></div>

    {{-- Reticle Box & Laser (decorative: the decoder crop is announced via the modal label) --}}
    <div x-show="isScanning" class="filament-qr-reticle" aria-hidden="true">
        <div class="filament-qr-reticle-box">
            <div class="filament-qr-reticle-corner top-left"></div>
            <div class="filament-qr-reticle-corner top-right"></div>
            <div class="filament-qr-reticle-corner bottom-left"></div>
            <div class="filament-qr-reticle-corner bottom-right"></div>
            <div class="filament-qr-laser"></div>
        </div>
    </div>

    {{-- Loading Spinner --}}
    <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 z-20">
        <div class="text-center text-white space-y-2">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            <p class="text-xs">{{ __('filament-qr-code::ui.starting_feed') }}</p>
        </div>
    </div>

    {{-- Error Message --}}
    <div x-show="hasError" class="absolute inset-0 flex items-center justify-center bg-gray-950/90 z-20 p-4">
        <div class="text-center text-danger-400 space-y-3 max-w-xs">
            <svg class="w-8 h-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-xs font-medium" x-text="errorMessage"></p>
            <button
                type="button"
                @click="loadCamerasAndStart()"
                class="px-3 py-1.5 text-xs font-medium bg-primary-600 hover:bg-primary-700 text-white rounded-md"
            >
                {{ __('filament-qr-code::ui.retry') }}
            </button>
        </div>
    </div>
</div>
