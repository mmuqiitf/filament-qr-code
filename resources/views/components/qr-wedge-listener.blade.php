@php
    $fields = $getFields();
    $hasSound = $hasSound();
    $hasVibration = $hasVibration();
    $burstThresholdMs = $getBurstThresholdMs();
    $preventSubmit = $shouldPreventFormSubmit();
    $autoFocusNext = $isAutoFocusNext();
@endphp

<div
    x-data="qrWedgeListener({
        fields: @js($fields),
        burstThresholdMs: @js($burstThresholdMs),
        preventSubmit: @js($preventSubmit),
        sound: @js($hasSound),
        vibrate: @js($hasVibration),
        autoFocusNext: @js($autoFocusNext)
    })"
    class="hidden"
></div>
