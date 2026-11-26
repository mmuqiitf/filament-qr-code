<div data-qr-part="collector.header" class="fi-qr-collector-header flex items-center justify-between">
    <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full" :class="isScanning ? 'bg-success-500 animate-pulse' : 'bg-gray-400 dark:bg-gray-300'"></span>
        <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ __('filament-qr-code::ui.batch_qr_collector') }}</span>
        <span class="text-xs bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-bold px-2 py-0.5 rounded-full">
            @if(($maxItems ?? null) !== null)<span x-text="items.length"></span> / {{ $maxItems }}@else<span x-text="items.length"></span>@endif {{ __('filament-qr-code::ui.items') }}@if($hasQuantity ?? false) (<span x-text="totalQuantity()"></span> {{ __('filament-qr-code::ui.total_quantity') }})@endif
        </span>
    </div>

    <div class="flex items-center gap-2">
        <x-filament::button
            type="button"
            size="xs"
            color="success"
            x-show="!isScanning"
            @click="startCollector()"
        >
            {{ __('filament-qr-code::ui.start_scanning') }}
        </x-filament::button>
        <x-filament::button
            type="button"
            size="xs"
            color="danger"
            x-show="isScanning"
            @click="stopCollector()"
        >
            {{ __('filament-qr-code::ui.pause') }}
        </x-filament::button>
    </div>
</div>
