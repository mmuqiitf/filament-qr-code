{{-- Continuous mode: last rejection and the paused banner --}}
<div data-qr-part="scanner.status"
    x-show="rejectionMessage !== '' || isPaused"
    x-cloak
    role="status"
    aria-live="polite"
    class="fi-qr-scanner-status mt-2 flex items-center justify-between gap-2 text-sm text-danger-600 dark:text-danger-400"
    @keydown.escape.window="resumeScanning()"
>
    <span>
        <span x-text="rejectionMessage"></span>
        <span x-show="isPaused" class="font-medium">{{ __('filament-qr-code::ui.scanning_paused') }}</span>
    </span>

    <button
        type="button"
        x-show="isPaused"
        @click="resumeScanning()"
        class="shrink-0 rounded-md bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-primary-500"
    >
        {{ __('filament-qr-code::ui.resume_scanning') }}
    </button>
</div>
