<div data-qr-part="sequence.step-chip"
    class="fi-qr-sequence-step-chip w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold"
    :class="results[f.key]
        ? 'bg-success-500 text-white'
        : (currentFieldIndex === idx ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300')"
>
    <span x-show="!results[f.key]" x-text="idx + 1"></span>
    <svg x-show="results[f.key]" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
    </svg>
</div>
