{{-- Scanner Modal --}}
<div data-qr-part="scanner.modal"
    x-show="isModalOpen"
    x-cloak
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-scanner-title"
    class="fi-qr-scanner-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-sm"
    @keydown.escape.window="closeScannerModal()"
>
    <div
        @click.outside="closeScannerModal()"
        data-qr-scanner-panel
        class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-800"
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SCANNER_MODAL_START, scopes: $renderHookScopes) }}
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                </span>
                <h2 id="{{ $id }}-scanner-title" class="font-semibold text-gray-900 dark:text-white text-sm">
                    {{ __('filament-qr-code::ui.scan_qr_code') }}
                </h2>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="closeScannerModal()"
                    aria-label="{{ __('filament-qr-code::ui.close_scanner') }}"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg"
                >
                    <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Camera Feed Container (Livewire-ignored: morphs would kill the video element mid-scan) --}}
        <div class="p-4 space-y-3" wire:ignore>
            @include('filament-qr-code::components.scanner.modal-camera')

            @include('filament-qr-code::components.scanner.modal-actions')
        </div>
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SCANNER_MODAL_END, scopes: $renderHookScopes) }}
    </div>
</div>
