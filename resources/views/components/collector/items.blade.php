<div data-qr-part="collector.items" class="fi-qr-collector-items md:col-span-6 space-y-2">
    <div class="flex items-center justify-between pb-1">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('filament-qr-code::ui.scanned_items_list') }}</span>
        <button
            x-show="items.length > 0"
            type="button"
            @click="clearAll()"
            class="text-xs text-danger-600 dark:text-danger-400 hover:underline"
        >
            {{ __('filament-qr-code::ui.clear_all') }}
        </button>
    </div>

    <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1">
        <template x-for="(item, index) in items" :key="index">
            <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs font-mono border border-gray-100 dark:border-gray-700">
                <div class="truncate mr-2">
                    <span class="font-bold text-gray-900 dark:text-white" x-text="item.code"></span>
                    <span class="text-[10px] text-gray-400 block" x-text="item.scanned_at"></span>
                </div>
                <button
                    type="button"
                    @click="removeItem(index)"
                    class="text-gray-400 hover:text-danger-600 p-1"
                >
                    &times;
                </button>
            </div>
        </template>
        <div x-show="items.length === 0" class="text-center py-6 text-xs text-gray-400">
            {{ __('filament-qr-code::ui.empty_collector') }}
        </div>
    </div>
</div>
