@php
    $thumbUri = $getThumbnailDataUri();
    $modalUri = $getModalDataUri();
    $isPreviewable = $isPreviewable();
    $isDownloadable = $isDownloadable();
    $thumbSize = $getThumbnailSize();
@endphp

@if ($thumbUri)
    <div x-data="{ isModalOpen: false }" class="inline-block py-1">
        <img
            src="{{ $thumbUri }}"
            alt="QR Code"
            style="width: {{ $thumbSize }}px; height: {{ $thumbSize }}px;"
            class="rounded-md border border-gray-200 dark:border-gray-700 bg-white p-0.5 {{ $isPreviewable ? 'cursor-pointer hover:opacity-80 transition' : '' }}"
            @if ($isPreviewable)
                @click="isModalOpen = true"
            @endif
        />

        @if ($isPreviewable)
            <div
                x-show="isModalOpen"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-sm"
                @keydown.escape.window="isModalOpen = false"
                @click.outside="isModalOpen = false"
            >
                <div class="relative bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-2xl border border-gray-200 dark:border-gray-800 text-center space-y-4 max-w-xs w-full">
                    <div class="flex justify-between items-center pb-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ __('QR Code Preview') }}</span>
                        <button type="button" @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            &times;
                        </button>
                    </div>

                    <div class="flex justify-center p-2 bg-white rounded-xl">
                        <img src="{{ $modalUri }}" alt="QR Code Large" class="rounded-lg shadow-sm" />
                    </div>

                    @if ($isDownloadable)
                        <a
                            href="{{ $modalUri }}"
                            download="qrcode.{{ str_starts_with($modalUri, 'data:image/svg+xml') ? 'svg' : 'png' }}"
                            class="inline-flex items-center justify-center gap-1.5 w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-sm transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            {{ __('Download Image') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
@else
    <span class="text-gray-400 text-xs italic">-</span>
@endif
