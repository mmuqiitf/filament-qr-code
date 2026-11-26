<div data-qr-part="collector.items" class="fi-qr-collector-items md:col-span-6 space-y-2">
    <div class="flex items-center justify-between pb-1">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('filament-qr-code::ui.scanned_items_list') }}</span>
        @if(($csvImport ?? false) || ($csvExport ?? false))<div class="flex items-center gap-1.5">@endif
            @if($csvImport ?? false)
                <input
                    type="file"
                    accept=".csv,text/csv"
                    x-ref="csvInput"
                    data-qr-part="collector.csv-input"
                    aria-label="{{ __('filament-qr-code::ui.import_csv') }}"
                    class="fi-qr-sr-only"
                    @change="importCsvFile($event)"
                />
                <x-filament::button
                    type="button"
                    size="xs"
                    color="gray"
                    data-qr-part="collector.import-csv"
                    @click="$refs.csvInput.click()"
                >
                    {{ __('filament-qr-code::ui.import_csv') }}
                </x-filament::button>
            @endif
            @if($csvExport ?? false)
                <x-filament::button
                    type="button"
                    size="xs"
                    color="gray"
                    data-qr-part="collector.export-csv"
                    x-show="items.length > 0"
                    @click="exportCsv()"
                >
                    {{ __('filament-qr-code::ui.export_csv') }}
                </x-filament::button>
            @endif
            <button
                x-show="items.length > 0"
                type="button"
                @click="clearAll()"
                class="text-xs text-danger-600 dark:text-danger-400 hover:underline"
            >
                {{ __('filament-qr-code::ui.clear_all') }}
            </button>
        @if(($csvImport ?? false) || ($csvExport ?? false))</div>@endif
    </div>

    @if($csvImport ?? false)
        <div
            x-show="importReport !== null"
            data-qr-part="collector.import-report"
            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-2.5 text-xs space-y-1.5"
        >
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-gray-900 dark:text-white" x-text="importSummary()"></span>
                <button
                    type="button"
                    @click="importReport = null"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    aria-label="{{ __('filament-qr-code::ui.dismiss') }}"
                >
                    &times;
                </button>
            </div>
            <template x-if="importReport && importReport.rejected && importReport.rejected.length > 0">
                <ul class="space-y-0.5 text-danger-600 dark:text-danger-400 max-h-28 overflow-y-auto">
                    <template x-for="(err, idx) in importReport.rejected" :key="idx">
                        <li x-text="formatRejectedRow(err)"></li>
                    </template>
                </ul>
            </template>
        </div>
    @endif

    <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1">
        <template x-for="(item, index) in items" :key="index">
            <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs font-mono border border-gray-100 dark:border-gray-700">
                <div class="truncate mr-2">
                    <span class="font-bold text-gray-900 dark:text-white" x-text="item.code"></span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 block" x-text="item.scanned_at"></span>
                </div>
                @if($hasQuantity ?? false)<div class="flex items-center gap-1.5 shrink-0">@endif
                    @if($hasQuantity ?? false)
                        <div class="flex items-center gap-1" data-qr-part="collector.qty">
                            <x-filament::button
                                type="button"
                                size="xs"
                                color="gray"
                                x-bind:disabled="1 >= (Number(item.qty) || 1)"
                                @click="decrementQty(index)"
                                aria-label="{{ __('filament-qr-code::ui.decrease_quantity') }}"
                            >
                                -
                            </x-filament::button>
                            <input
                                type="number"
                                min="1"
                                step="1"
                                x-bind:value="item.qty ?? 1"
                                @change="setQty(index, $event.target.value)"
                                aria-label="{{ __('filament-qr-code::ui.quantity') }}"
                                class="w-14 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-center text-xs text-gray-900 dark:text-white py-1 px-1.5"
                            />
                            <x-filament::button
                                type="button"
                                size="xs"
                                color="gray"
                                @click="incrementQty(index)"
                                aria-label="{{ __('filament-qr-code::ui.increase_quantity') }}"
                            >
                                +
                            </x-filament::button>
                        </div>
                    @endif
                    <button
                        type="button"
                        @click="removeItem(index)"
                        class="text-gray-400 hover:text-danger-600 dark:text-gray-300 dark:hover:text-danger-400 p-1"
                        aria-label="{{ __('filament-qr-code::ui.remove_code') }}"
                    >
                        &times;
                    </button>
                @if($hasQuantity ?? false)</div>@endif
            </div>
        </template>
        <div x-show="items.length === 0" class="text-center py-6 text-xs text-gray-500 dark:text-gray-400">
            {{ __('filament-qr-code::ui.empty_collector') }}
        </div>
    </div>
</div>
