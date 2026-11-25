@php
    $thumbUri = $getThumbnailDataUri();
    $isPreviewable = $isPreviewable();
    $isDownloadable = $isDownloadable();
    $thumbSize = $getThumbnailSize();
    $isLazyModal = $isLazyModal();
    $modalUri = $isLazyModal ? $getModalUrl() : $getModalDataUri();
@endphp

@if ($thumbUri)
    <div x-data="{ isModalOpen: false }" class="inline-block py-1">
        <img
            src="{{ $thumbUri }}"
            alt="QR Code"
            style="width: {{ $thumbSize }}px; height: {{ $thumbSize }}px;"
            class="fi-qr-quiet-zone rounded-md border border-gray-200 dark:border-gray-700 bg-white p-0.5 {{ $isPreviewable ? 'cursor-pointer hover:opacity-80 transition' : '' }}"
            @if ($isPreviewable)
                @click="isModalOpen = true"
            @endif
        />

        @if ($isPreviewable)
            <div
                x-show="isModalOpen"
                x-cloak
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('filament-qr-code::ui.qr_preview') }}"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-sm"
                @keydown.escape.window="isModalOpen = false"
                @click.outside="isModalOpen = false"
            >
                <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-800 text-center space-y-4 max-w-xs w-full">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ __('filament-qr-code::ui.qr_preview') }}</span>
                        <button type="button" @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-200">
                            &times;
                        </button>
                    </div>

                    <div class="fi-qr-quiet-zone flex justify-center p-2 bg-white rounded-xl">
                        @if ($isLazyModal)
                            <img src="{{ $modalUri }}" alt="QR Code Large" class="rounded-lg shadow-sm" loading="lazy" />
                        @else
                            <img src="{{ $modalUri }}" alt="QR Code Large" class="rounded-lg shadow-sm" />
                        @endif
                    </div>

                    @if ($isDownloadable)
                        <x-filament::button
                            tag="a"
                            :href="$modalUri"
                            size="sm"
                            color="primary"
                            icon="heroicon-o-arrow-down-tray"
                            class="w-full"
                            download="qrcode.{{ $getModalExtension() }}"
                        >
                            {{ __('filament-qr-code::ui.download_image') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>
        @endif
    </div>
@else
    <span class="text-gray-500 dark:text-gray-400 text-xs italic">-</span>
@endif
