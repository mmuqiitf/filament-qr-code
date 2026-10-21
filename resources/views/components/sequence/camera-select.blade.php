<div data-qr-part="sequence.camera-select" class="fi-qr-sequence-camera-select space-y-2">
    <div x-show="devices.length > 1" class="flex items-center gap-2 text-xs">
        <label class="flex items-center gap-2 text-gray-500 dark:text-gray-400 shrink-0 w-full">{{ __('filament-qr-code::ui.camera') }}
        <select
            x-model="selectedDeviceId"
            @change="isScanning ? startScanner() : null"
            class="fi-select-input w-full rounded-md border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs text-gray-900 dark:text-white py-1 px-2"
        >
            <template x-for="dev in devices" :key="dev.id">
                <option :value="dev.id" x-text="dev.label || ('Camera ' + dev.id)"></option>
            </template>
        </select>
        </label>
    </div>
</div>
