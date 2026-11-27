<div
    data-qr-part="offline.banner"
    x-show="isOnline === false && offlineMode !== 'off'"
    x-cloak
    role="status"
    class="fi-qr-offline-banner rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200"
>
    {{ __('filament-qr-code::ui.offline_banner') }}
</div>
