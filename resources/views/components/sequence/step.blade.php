<div data-qr-part="sequence.step"
    @click="setCurrentField(idx)"
    :data-qr-step-active="isActive(f.key) ? 'true' : 'false'"
    :style="isActive(f.key) ? '' : 'opacity: 0.5'"
    class="fi-qr-sequence-step p-3 rounded-xl border transition cursor-pointer flex items-center justify-between text-sm"
    :class="currentFieldIndex === idx
        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/30 text-primary-900 dark:text-primary-100 ring-1 ring-primary-500'
        : (results[f.key] ? 'border-success-300 dark:border-success-800 bg-success-50/30 dark:bg-success-950/10' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900')"
>
    <div class="flex items-center gap-2.5">
        @include('filament-qr-code::components.sequence.step-chip')
        <span class="font-medium" x-text="f.label"></span>
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SEQUENCE_STEP_HEADER, scopes: $renderHookScopes) }}
        <span
            x-show="stepProgress(f.key) !== null"
            x-text="stepProgress(f.key)"
            class="rounded-full bg-gray-100 px-2 py-0.5 font-mono text-[11px] text-gray-700 dark:bg-gray-800 dark:text-gray-300"
        ></span>
        <button
            x-show="isSticky(f.key) && results[f.key]"
            type="button"
            @click.stop="clearStep(f.key)"
            class="rounded-md border border-gray-300 px-2 py-0.5 text-[11px] font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
        >
            {{ __('filament-qr-code::ui.clear') }}
        </button>
        <button
            x-show="isRepeating(f.key) && currentFieldIndex === idx && stepProgress(f.key) !== null"
            type="button"
            @click.stop="finishStep(f.key)"
            class="rounded-md bg-primary-600 px-2 py-0.5 text-[11px] font-semibold text-white hover:bg-primary-700"
        >
            {{ __('filament-qr-code::ui.done') }}
        </button>
    </div>

    @include('filament-qr-code::components.sequence.step-value')
</div>
