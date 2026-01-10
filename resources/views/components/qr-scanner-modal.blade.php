{{-- resources/views/components/qr-scanner-modal.blade.php --}}
<div x-data="qrScanner({
    statePath: @js($statePath),
    cameraFacing: @js($cameraFacing->value),
    scanMode: @js($scanMode->value),
    scanDelay: @js($scanDelay),
    fps: @js($fps ?? 30),
    qrboxSize: @js($qrboxSize ?? 250),
    showPreview: @js($showPreview ?? true),
    beepOnScan: @js($beepOnScan ?? true),
    vibrateOnScan: @js($vibrateOnScan ?? true),
})" x-init="init()" x-on:destroy.window="destroy()" class="qr-scanner-container">
    {{-- Camera Selection --}}
    <div class="mb-4" x-show="devices.length > 1" x-cloak>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('filament-qr-code::messages.select_camera') }}
        </label>
        <select x-model="selectedDeviceId" x-on:change="selectCamera($event.target.value)"
            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm
                   focus:border-primary-500 focus:ring-primary-500
                   dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <template x-for="device in devices" :key="device.id">
                <option :value="device.id" x-text="device.label || 'Camera ' + device.id"></option>
            </template>
        </select>
    </div>

    {{-- Scanner Container --}}
    <div class="relative rounded-lg overflow-hidden bg-black aspect-video">
        <div :id="readerId" class="w-full h-full"></div>

        {{-- Loading State --}}
        <div x-show="isLoading" x-cloak class="absolute inset-0 flex items-center justify-center bg-gray-900/80">
            <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>

        {{-- Error State --}}
        <div x-show="hasError" x-cloak
            class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900/80 text-white p-4">
            <svg class="h-12 w-12 text-danger-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <p x-text="errorMessage" class="text-center text-sm"></p>
            <button x-on:click="retryCamera()" type="button"
                class="mt-4 inline-flex items-center gap-1 justify-center rounded-lg border border-transparent bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                {{ __('filament-qr-code::messages.retry') }}
            </button>
        </div>

        {{-- Scanning Indicator --}}
        <div x-show="isScanning && !hasError && !isLoading" x-cloak
            class="absolute bottom-4 left-0 right-0 flex justify-center">
            <span class="bg-green-500 text-white text-sm px-3 py-1 rounded-full animate-pulse">
                {{ __('filament-qr-code::messages.scanning') }}
            </span>
        </div>
    </div>

    {{-- Scanned Result Preview --}}
    <div x-show="showPreview && lastScannedValue" x-cloak class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('filament-qr-code::messages.scanned_value') }}
        </label>
        <p x-text="lastScannedValue" class="mt-1 text-gray-900 dark:text-white font-mono text-sm break-all"></p>
    </div>

    {{-- Manual Input Fallback --}}
    <div class="mt-4">
        <button x-on:click="toggleManualInput()" type="button"
            class="inline-flex items-center gap-1 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            {{ __('filament-qr-code::messages.enter_manually') }}
        </button>

        <div x-show="showManualInput" x-cloak class="mt-2 space-y-2">
            <input type="text" x-model="manualValue" x-ref="manualInput"
                x-on:keydown.enter.prevent="submitManualValue()"
                class="block w-full rounded-lg border-gray-300 shadow-sm
                       focus:border-primary-500 focus:ring-primary-500
                       dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                placeholder="{{ __('filament-qr-code::messages.enter_code') }}">
            <button x-on:click="submitManualValue()" type="button"
                class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                {{ __('filament-qr-code::messages.submit') }}
            </button>
        </div>
    </div>
</div>
