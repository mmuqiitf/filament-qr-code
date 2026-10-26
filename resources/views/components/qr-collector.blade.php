@php
    $statePath = $getStatePath();
    $scanPayload = $getScanPayload();
    $extraAttributes = $getExtraAttributeBag();
    $renderHookScopes = $getRenderHookScopes();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="qrCollector({
            state: $wire.entangle('{{ $statePath }}'),
            ...@js($scanPayload)
        })"
        {{ $extraAttributes->except('class') }}
        @class(['fi-qr-collector space-y-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4', $extraAttributes->get('class')])
        wire:ignore
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::COLLECTOR_START, scopes: $renderHookScopes) }}
        @include('filament-qr-code::components.collector.header')
        @include('filament-qr-code::components.announcer')

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
            @include('filament-qr-code::components.collector.camera')

            @include('filament-qr-code::components.collector.items')
        </div>
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::COLLECTOR_END, scopes: $renderHookScopes) }}
    </div>
</x-dynamic-component>
