{{-- Visible rejection; the announcer live region reads it once. --}}
<p data-qr-part="sequence.status"
    x-show="stepError"
    x-text="stepError"
    class="fi-qr-sequence-status rounded-lg border border-danger-300 bg-danger-50 px-3 py-2 text-xs font-medium text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-200"
    x-cloak
></p>
{{-- Paused by pause() or a qr-sequence-pause event: scans are ignored until resumed. --}}
<div data-qr-part="sequence.paused"
    x-show="isPaused"
    role="status"
    aria-live="polite"
    class="fi-qr-sequence-paused flex items-center justify-between gap-2 rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-800 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-200"
    x-cloak
>
    <span>{{ __('filament-qr-code::ui.scanning_paused') }}</span>
    <button type="button" @click="resume()" class="shrink-0 rounded-md bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-primary-500">
        {{ __('filament-qr-code::ui.resume_scanning') }}
    </button>
</div>
