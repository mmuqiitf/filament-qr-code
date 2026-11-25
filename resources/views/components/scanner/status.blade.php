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

    <x-filament::button
        type="button"
        size="sm"
        color="primary"
        class="shrink-0"
        x-show="isPaused"
        @click="resumeScanning()"
    >
        {{ __('filament-qr-code::ui.resume_scanning') }}
    </x-filament::button>
</div>
