@php
    $scanPayload = $getScanPayload();
@endphp

<div
    x-data="qrHardwareScannerListener({
        ...@js($scanPayload)
    })"
    class="hidden"
></div>
