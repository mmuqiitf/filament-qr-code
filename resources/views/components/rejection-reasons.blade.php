<div
    data-qr-part="scanner.reasons"
    x-show="rejectionReasons.length > 0"
    x-cloak
    class="fi-qr-rejection-reasons mt-2 rounded-lg border border-danger-200 bg-danger-50 px-3 py-2 text-xs text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-200"
>
    <p class="font-medium">{{ __('filament-qr-code::ui.rejection_reasons') }}</p>
    <ul class="mt-1 list-disc ps-4">
        <template x-for="(reason, index) in rejectionReasons" :key="index">
            <li x-text="reason"></li>
        </template>
    </ul>
</div>
