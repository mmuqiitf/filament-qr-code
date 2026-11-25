<div data-qr-part="sequence.header" class="fi-qr-sequence-header flex items-center justify-between">
    <p class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-2">
        <span class="w-2 h-2 rounded-full" :class="isScanning ? 'bg-success-500 animate-pulse' : 'bg-gray-400 dark:bg-gray-300'"></span>
        {{ __('filament-qr-code::ui.sequence_scanner') }}
    </p>

    <div class="flex items-center gap-2">
        <x-filament::button
            type="button"
            size="xs"
            color="success"
            x-show="!isScanning"
            @click="startScanner()"
        >
            {{ __('filament-qr-code::ui.start') }}
        </x-filament::button>
        <x-filament::button
            type="button"
            size="xs"
            color="danger"
            x-show="isScanning"
            @click="stopScanner()"
        >
            {{ __('filament-qr-code::ui.stop') }}
        </x-filament::button>
    </div>
</div>
