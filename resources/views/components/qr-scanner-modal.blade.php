{{-- resources/views/components/qr-scanner-modal.blade.php --}}
<div x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('qr-scanner', 'mmuqiitf/filament-qr-code') }}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('qr-code-styles', package: 'mmuqiitf/filament-qr-code'))]" x-data="qrScannerComponent({
        statePath: @js($statePath),
        cameraFacing: @js($cameraFacing->value),
        scanMode: @js($scanMode->value),
        scanDelay: @js($scanDelay),
        fps: @js($fps ?? 30),
        qrboxSize: @js($qrboxSize ?? 250),
        showPreview: @js($showPreview ?? true),
        beepOnScan: @js($beepOnScan ?? true),
        vibrateOnScan: @js($vibrateOnScan ?? true),
    })" x-on:destroy.window="destroy()"
    class="qr-scanner-container">
    {{-- Camera Selection with smooth fade --}}
    <div x-show="devices.length > 1" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        class="mb-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ __('filament-qr-code::messages.select_camera') }}
        </label>
        <select x-model="selectedDeviceId" x-on:change="selectCamera($event.target.value)"
            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm
                   focus:border-primary-500 focus:ring-primary-500 transition duration-75
                   dark:bg-gray-700 dark:text-white">
            <template x-for="device in devices" :key="device.id">
                <option :value="device.id" x-text="device.label || 'Camera ' + device.id"></option>
            </template>
        </select>
    </div>

    {{-- Scanner Container with smooth transitions --}}
    <div class="relative rounded-xl overflow-hidden bg-gray-900 aspect-video shadow-xl transition-all duration-300 ease-in-out"
        :class="{ 'ring-2 ring-primary-500': isScanning && !hasError && !isLoading }">
        <div :id="readerId" class="w-full h-full"></div>

        {{-- Loading State with smooth fade and backdrop blur --}}
        <div x-show="isLoading" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900/90 backdrop-blur-sm">
            <div class="text-center">
                <svg class="animate-spin h-12 w-12 text-primary-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="text-white text-sm font-medium">
                    {{ __('filament-qr-code::messages.initializing_camera') }}
                </p>
            </div>
        </div>

        {{-- Error State with smooth fade and scale --}}
        <div x-show="hasError" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900/90 backdrop-blur-sm text-white p-6">
            <div class="text-center max-w-sm">
                <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-danger-500/20">
                    <svg class="h-8 w-8 text-danger-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </div>
                <p x-text="errorMessage" class="text-center text-sm leading-relaxed mb-4"></p>
                <button x-on:click="retryCamera()" type="button"
                    class="inline-flex items-center gap-2 justify-center rounded-lg bg-white/10 hover:bg-white/20 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-gray-900">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    {{ __('filament-qr-code::messages.retry') }}
                </button>
            </div>
        </div>

        {{-- Scanning Indicator with pulse animation --}}
        <div x-show="isScanning && !hasError && !isLoading" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="absolute bottom-4 left-0 right-0 flex justify-center pointer-events-none">
            <span
                class="bg-green-500 text-white text-sm font-medium px-4 py-2 rounded-full shadow-lg flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                </span>
                {{ __('filament-qr-code::messages.scanning') }}
            </span>
        </div>
    </div>

    {{-- Scanned Result Preview with smooth fade --}}
    <div x-show="showPreview && lastScannedValue" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="mt-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
        <label class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-400">
            {{ __('filament-qr-code::messages.scanned_value') }}
        </label>
        <p x-text="lastScannedValue" class="mt-2 text-gray-900 dark:text-white font-mono text-sm break-all"></p>
    </div>

    {{-- Manual Input Fallback with smooth transitions --}}
    <div class="mt-4">
        <button x-on:click="toggleManualInput()" type="button"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors duration-200">
            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            {{ __('filament-qr-code::messages.enter_manually') }}
        </button>

        <div x-show="showManualInput" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1" class="mt-3 space-y-3">
            <input type="text" x-model="manualValue" x-ref="manualInput"
                x-on:keydown.enter.prevent="submitManualValue()"
                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm
                       focus:border-primary-500 focus:ring-primary-500 transition duration-75
                       dark:bg-gray-700 dark:text-white text-sm"
                placeholder="{{ __('filament-qr-code::messages.enter_code') }}">
            <button x-on:click="submitManualValue()" type="button"
                class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                {{ __('filament-qr-code::messages.submit') }}
            </button>
        </div>
    </div>
</div>
