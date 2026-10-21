<div data-qr-part="sequence.header" class="fi-qr-sequence-header flex items-center justify-between">
    <h4 class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-2">
        <span class="w-2 h-2 rounded-full" :class="isScanning ? 'bg-success-500 animate-pulse' : 'bg-gray-400'"></span>
        {{ __('filament-qr-code::ui.sequence_scanner') }}
    </h4>

    <div class="flex items-center gap-2">
        <button
            x-show="!isScanning"
            type="button"
            @click="startScanner()"
            class="px-3 py-1 bg-success-600 hover:bg-success-700 text-white rounded-lg text-xs font-semibold shadow-sm"
        >
            {{ __('filament-qr-code::ui.start') }}
        </button>
        <button
            x-show="isScanning"
            type="button"
            @click="stopScanner()"
            class="px-3 py-1 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-xs font-semibold shadow-sm"
        >
            {{ __('filament-qr-code::ui.stop') }}
        </button>
    </div>
</div>
