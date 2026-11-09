@php
    $scanPayload = $getScanPayload();
@endphp

<div
    x-data="qrHardwareScannerListener({
        ...@js($scanPayload)
    })"
    @class(['hidden' => ! $isGs1()])
>
    @if ($isGs1())
        {{-- GS1 mode: why the last label was refused --}}
        <p
            data-qr-part="listener.status"
            x-show="rejectionMessage !== ''"
            x-cloak
            x-text="rejectionMessage"
            role="status"
            aria-live="polite"
            class="fi-qr-listener-status text-sm text-danger-600 dark:text-danger-400"
        ></p>
    @endif
</div>
