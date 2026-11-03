@php($compact ??= false)

@if ($render['uri'])
    <div @class([
        'fi-qr-barcode inline-flex flex-col items-center gap-1',
        'p-3 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm' => ! $compact,
        'py-1' => $compact,
    ]) data-qr-part="barcode">
        <img src="{{ $render['uri'] }}" alt="{{ $render['alt'] }}" class="max-w-full h-auto" data-qr-part="barcode.image" />

        @if ($downloadable)
            <a
                href="{{ $render['uri'] }}"
                download="barcode.{{ $render['extension'] }}"
                class="text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
                data-qr-part="barcode.download"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                {{ __('filament-qr-code::ui.download') }}
            </a>
        @endif
    </div>
@elseif ($render['error'])
    <div role="alert" class="fi-qr-barcode-error text-xs font-medium text-danger-600 dark:text-danger-400" data-qr-part="barcode.error">
        {{ $render['error'] }}
    </div>
@elseif ($compact)
    <span class="text-gray-500 text-xs italic">-</span>
@else
    <div class="text-xs text-gray-500 dark:text-gray-400 italic">
        {{ __('filament-qr-code::ui.barcode_no_data') }}
    </div>
@endif
