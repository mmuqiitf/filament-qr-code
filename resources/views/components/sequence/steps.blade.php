<div data-qr-part="sequence.steps" class="fi-qr-sequence-steps lg:col-span-7 space-y-3">
    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            {{ __('filament-qr-code::ui.sequential_steps') }}
        </span>
        <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
            <span x-text="Object.keys(results).length"></span> / <span x-text="fields.length"></span> {{ __('filament-qr-code::ui.captured') }}
        </span>
    </div>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SEQUENCE_STEPS_BEFORE, scopes: $renderHookScopes) }}
    <div class="space-y-2">
        <template x-for="(f, idx) in fields" :key="f.key">
            @include('filament-qr-code::components.sequence.step')
        </template>
    </div>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SEQUENCE_STEPS_AFTER, scopes: $renderHookScopes) }}
</div>
