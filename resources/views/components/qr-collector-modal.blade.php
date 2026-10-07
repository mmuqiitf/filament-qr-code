<div
    x-data="qrCollector({
        allowDuplicates: @js($allowDuplicates ?? false),
        sound: @js($sound ?? true),
        vibrate: @js($vibrate ?? true),
        beepFrequency: @js($beepFrequency ?? 880),
        beepDurationMs: @js($beepDuration ?? 80),
        vibrateDurationMs: @js($vibrateDuration ?? 100),
        hardwareScanner: @js($hardwareScanner ?? true),
        burstThresholdMs: @js($burstThresholdMs ?? 50),
        terminators: @js($terminators ?? ['Enter', 'Tab']),
        minBarcodeLength: @js($minBarcodeLength ?? 2),
        scanTimeoutMs: @js($scanTimeoutMs ?? 150),
        suppressWhenGlobalListenerActive: @js($suppressWhenGlobal ?? true),
        insecureMessage: @js($insecureMessage ?? __('filament-qr-code::ui.camera_needs_secure_context')),
        fps: @js($fps ?? 25),
        qrbox: @js($qrbox ?? 250),
        preferRearCamera: @js($preferRearCamera ?? true),
        formats: @js($formats ?? []),
        cameraStorageKey: @js('filament-qr-code:camera:collect-action:' . ($actionName ?? 'default'))
    })"
    class="space-y-4"
    wire:ignore
>
    <div x-show="devices.length > 1" class="flex items-center gap-2 text-xs">
        <label class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('filament-qr-code::ui.camera') }}</label>
        <select
            x-model="selectedDeviceId"
            @change="isScanning ? startCollector() : null"
            class="fi-select-input w-full rounded-md border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs text-gray-900 dark:text-white py-1 px-2"
        >
            <template x-for="dev in devices" :key="dev.id">
                <option :value="dev.id" x-text="dev.label || ('Camera ' + dev.id)"></option>
            </template>
        </select>
    </div>

    <div class="filament-qr-viewfinder">
        <div :id="elementId" class="w-full h-full"></div>
        <div x-show="isScanning" class="filament-qr-reticle">
            <div class="filament-qr-reticle-box">
                <div class="filament-qr-reticle-corner top-left"></div>
                <div class="filament-qr-reticle-corner top-right"></div>
                <div class="filament-qr-reticle-corner bottom-left"></div>
                <div class="filament-qr-reticle-corner bottom-right"></div>
                <div class="filament-qr-laser"></div>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <button
            x-show="!isScanning"
            type="button"
            @click="startCollector()"
            class="px-4 py-2 bg-success-600 hover:bg-success-700 text-white rounded-lg text-sm font-semibold shadow-sm w-full"
        >
            {{ __('filament-qr-code::ui.start_camera_scanner') }}
        </button>
        <button
            x-show="isScanning"
            type="button"
            @click="stopCollector()"
            class="px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-sm font-semibold shadow-sm w-full"
        >
            {{ __('filament-qr-code::ui.pause_scanner') }}
        </button>
    </div>

    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                {{ __('filament-qr-code::ui.scanned_codes') }} (<span x-text="items.length"></span>)
            </span>
            <button
                x-show="items.length > 0"
                type="button"
                @click="clearAll()"
                class="text-xs text-danger-600 hover:underline"
            >
                {{ __('filament-qr-code::ui.clear_all') }}
            </button>
        </div>

        <div class="max-h-48 overflow-y-auto space-y-1">
            <template x-for="(item, index) in items" :key="index">
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs font-mono">
                    <span class="font-bold text-gray-900 dark:text-white truncate" x-text="item.code"></span>
                    <button type="button" @click="removeItem(index)" class="text-gray-400 hover:text-danger-600 ml-2">
                        &times;
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
