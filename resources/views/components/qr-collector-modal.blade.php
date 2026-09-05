<div
    x-data="qrCollector({
        allowDuplicates: @js($allowDuplicates ?? false),
        sound: @js($sound ?? true),
        vibrate: @js($vibrate ?? true),
        hardwareScanner: @js($hardwareScanner ?? true),
        fps: @js($fps ?? 15),
        qrbox: @js($qrbox ?? 250)
    })"
    class="space-y-4"
>
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

    <div class="flex items-center justify-between">
        <button
            x-show="!isScanning"
            type="button"
            @click="startCollector()"
            class="px-4 py-2 bg-success-600 hover:bg-success-700 text-white rounded-lg text-sm font-semibold shadow-sm w-full"
        >
            {{ __('Start Camera Scanner') }}
        </button>
        <button
            x-show="isScanning"
            type="button"
            @click="stopCollector()"
            class="px-4 py-2 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-sm font-semibold shadow-sm w-full"
        >
            {{ __('Pause Scanner') }}
        </button>
    </div>

    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                {{ __('Scanned Codes') }} (<span x-text="items.length"></span>)
            </span>
            <button
                x-show="items.length > 0"
                type="button"
                @click="clearAll()"
                class="text-xs text-danger-600 hover:underline"
            >
                {{ __('Clear All') }}
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
