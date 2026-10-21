<div data-qr-part="collector.header" class="fi-qr-collector-header flex items-center justify-between">
    <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full" :class="isScanning ? 'bg-success-500 animate-pulse' : 'bg-gray-400'"></span>
        <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ __('filament-qr-code::ui.batch_qr_collector') }}</span>
        <span class="text-xs bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-bold px-2 py-0.5 rounded-full">
            <span x-text="items.length"></span> {{ __('filament-qr-code::ui.items') }}
        </span>
    </div>

    <div class="flex items-center gap-2">
        <button
            x-show="!isScanning"
            type="button"
            @click="startCollector()"
            class="px-3 py-1.5 bg-success-600 hover:bg-success-700 text-white rounded-lg text-xs font-semibold"
        >
            {{ __('filament-qr-code::ui.start_scanning') }}
        </button>
        <button
            x-show="isScanning"
            type="button"
            @click="stopCollector()"
            class="px-3 py-1.5 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-xs font-semibold"
        >
            {{ __('filament-qr-code::ui.pause') }}
        </button>
    </div>
</div>
