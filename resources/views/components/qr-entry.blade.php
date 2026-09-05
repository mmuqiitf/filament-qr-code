@php
    $qrDataUri = $getQrDataUri();
    $isDownloadable = $isDownloadable();
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    @if ($qrDataUri)
        <div class="inline-flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm space-y-2">
            <img src="{{ $qrDataUri }}" alt="QR Code" class="rounded-lg max-w-full h-auto" />

            @if ($isDownloadable)
                <a
                    href="{{ $qrDataUri }}"
                    download="qrcode.{{ str_starts_with($qrDataUri, 'data:image/svg+xml') ? 'svg' : 'png' }}"
                    class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1 mt-1"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('Download') }}
                </a>
            @endif
        </div>
    @else
        <div class="text-xs text-gray-400 italic">
            {{ __('No QR code data available') }}
        </div>
    @endif
</x-dynamic-component>
