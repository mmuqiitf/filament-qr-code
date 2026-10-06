@php
    $fields = $getFields();
    $hasSound = $hasSound();
    $hasVibration = $hasVibration();
    $burstThresholdMs = $getBurstThresholdMs();
    $preventSubmit = $shouldPreventFormSubmit();
    $autoFocusNext = $isAutoFocusNext();
    $beepFrequency = $getBeepFrequencyHz();
    $beepDuration = $getBeepDurationMs();
    $vibrateDuration = $getVibrateDurationMs();
    $terminators = $getTerminators();
    $minBarcodeLength = $getMinBarcodeLength();
@endphp

<div
    x-data="qrHardwareScannerListener({
        fields: @js($fields),
        burstThresholdMs: @js($burstThresholdMs),
        preventSubmit: @js($preventSubmit),
        sound: @js($hasSound),
        vibrate: @js($hasVibration),
        beepFrequency: @js($beepFrequency),
        beepDurationMs: @js($beepDuration),
        vibrateDurationMs: @js($vibrateDuration),
        terminators: @js($terminators),
        minBarcodeLength: @js($minBarcodeLength),
        autoFocusNext: @js($autoFocusNext)
    })"
    class="hidden"
></div>
