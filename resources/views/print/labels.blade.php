@foreach ($pages as $page)
    <div class="filament-qr-label-sheet" data-fq-sheet @if ($sheet->pageSize) data-fq-page="{{ $sheet->pageSize }}" @endif style="{{ $sheet->cssStyle() }}">
        @foreach ($page as $label)
            @if ($label === null)
                <div class="filament-qr-label filament-qr-label--empty" aria-hidden="true"></div>
            @else
                <figure class="filament-qr-label">
                    @if ($label->getHeading())
                        <div class="filament-qr-label__heading">{{ $label->getHeading() }}</div>
                    @endif
                    <div class="filament-qr-label__symbols">
                        @if ($qr = $label->qrDataUri($format))
                            <img class="filament-qr-label__qr" src="{{ $qr }}" alt="{{ $label->getHeading() ?: $label->getCaption() ?: __('filament-qr-code::ui.print_qr_alt') }}">
                        @endif
                        @if ($barcode = $label->barcodeDataUri($format))
                            <img class="filament-qr-label__barcode" src="{{ $barcode }}" alt="{{ $label->getHeading() ?: $label->getCaption() ?: __('filament-qr-code::ui.print_barcode_alt') }}">
                        @endif
                    </div>
                    @if ($label->getCaption())
                        <figcaption class="filament-qr-label__caption">{{ $label->getCaption() }}</figcaption>
                    @endif
                </figure>
            @endif
        @endforeach
    </div>
@endforeach
