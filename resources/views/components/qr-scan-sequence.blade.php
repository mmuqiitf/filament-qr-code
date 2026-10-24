@php
    $scanPayload = $getScanPayload();
    $extraAttributes = $getExtraAttributeBag();
    $renderHookScopes = $getRenderHookScopes();
@endphp

{{-- Livewire morphs data-qr-options on this wrapper; the scanning UI below stays wire:ignore'd. --}}
<div data-qr-options="{{ json_encode($scanPayload, JSON_THROW_ON_ERROR) }}">
<div
    data-qr-sequence
    x-data="qrScanSequence({
        ...@js($scanPayload)
    })"
    @qr-sequence-editable.window="editable = $event.detail.enabled"
    {{ $extraAttributes->except('class') }}
    @class(['fi-qr-sequence grid grid-cols-1 lg:grid-cols-12 gap-6 items-start rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 shadow-sm', $extraAttributes->get('class')])
    wire:ignore
>
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SEQUENCE_START, scopes: $renderHookScopes) }}
    {{-- Left: Scanner Viewfinder & Controls --}}
    <div class="lg:col-span-5 space-y-4">
        @include('filament-qr-code::components.sequence.header')

        @include('filament-qr-code::components.sequence.camera-select')

        @include('filament-qr-code::components.sequence.status')

        @include('filament-qr-code::components.sequence.camera')
    </div>

    {{-- Right: Sequential Fields Checklist & Active Target --}}
    @include('filament-qr-code::components.sequence.steps')
    {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SEQUENCE_END, scopes: $renderHookScopes) }}
</div>
</div>
