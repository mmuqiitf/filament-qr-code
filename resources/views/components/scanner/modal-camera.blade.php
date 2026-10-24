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

    @include('filament-qr-code::components.camera-status', ['retry' => 'loadCamerasAndStart()'])
</div>
