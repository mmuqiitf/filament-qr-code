<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @include('filament-qr-code::components.barcode-image', ['render' => $getBarcodeRender(), 'downloadable' => $isDownloadable()])
</x-dynamic-component>
