<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @include('filament-qr-code::components.barcode-image', ['render' => $getBarcodeRender(), 'downloadable' => $isDownloadable()])
</x-dynamic-component>
