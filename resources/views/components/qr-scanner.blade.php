@php
    $statePath = $getStatePath();
    $id = $getId();
    $isDisabled = $isDisabled();
    $allowUpload = $isUploadAllowed();
    $placeholder = $getPlaceholder();
    $scanPayload = $getScanPayload();
    $extraAttributes = $getExtraAttributeBag();
    $renderHookScopes = $getRenderHookScopes();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="qrScanner({ state: $wire.entangle('{{ $statePath }}') })"
        data-field-name="{{ $getName() }}"
        data-qr-scanner
        data-qr-options="{{ json_encode($scanPayload, JSON_THROW_ON_ERROR) }}"
        {{ $extraAttributes->except('class') }}
        @class(['fi-qr-scanner relative rounded-lg', $extraAttributes->get('class')])
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SCANNER_START, scopes: $renderHookScopes) }}
        <div class="flex rounded-lg shadow-sm">
            @include('filament-qr-code::components.scanner.input')

            @include('filament-qr-code::components.scanner.camera-button')
        </div>

        @include('filament-qr-code::components.scanner.status')

        @include('filament-qr-code::components.scanner.modal')
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Mmuqiitf\FilamentQrCode\View\QrCodeRenderHook::SCANNER_END, scopes: $renderHookScopes) }}
    </div>
</x-dynamic-component>
