{{-- Camera loading / error overlay shared by every viewfinder. $retry is the Alpine call for the Retry button. --}}
<div data-qr-part="camera.status" class="fi-qr-camera-status contents">
    <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 z-20">
        <div class="text-center text-white space-y-2">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
            <p class="text-xs">{{ __('filament-qr-code::ui.starting_feed') }}</p>
        </div>
    </div>

    <div x-show="hasError" class="absolute inset-0 flex items-center justify-center bg-gray-950/90 z-20 p-4">
        <div class="text-center text-danger-400 space-y-3 max-w-xs">
            <svg class="w-8 h-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-xs font-medium" role="alert" x-text="errorMessage"></p>
            <button
                type="button"
                @click="{{ $retry }}"
                class="px-3 py-1.5 text-xs font-medium bg-primary-600 hover:bg-primary-700 text-white rounded-md"
            >
                {{ __('filament-qr-code::ui.retry') }}
            </button>
        </div>
    </div>
</div>
