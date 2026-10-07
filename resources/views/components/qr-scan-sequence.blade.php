@php
    $fields = $getScanFields();
    $hasSound = $hasSound();
    $hasVibration = $hasVibration();
    $isHardwareScanner = $isHardwareScannerEnabled();
    $fps = $getEffectiveFps();
    $qrbox = $getQrbox();
    $preferRear = $isPreferRearCamera();
    $supportedFormats = $getSupportedFormats();
    $statePrefix = $getStatePathPrefix();
    $componentStatePath = $getComponentStatePath();
    $beepFrequency = $getBeepFrequencyHz();
    $beepDuration = $getBeepDurationMs();
    $vibrateDuration = $getVibrateDurationMs();
    $burstThresholdMs = $getBurstThresholdMs();
    $terminators = $getTerminators();
    $minBarcodeLength = $getMinBarcodeLength();
    $scanTimeoutMs = $getScanTimeoutMs();
    $suppressWhenGlobal = $isSuppressedWhenGlobalListenerActive();
    $isEditable = $isEditable();
    $prefixWarning = $prefixMismatchWarning();
    $insecureMessage = __('filament-qr-code::ui.camera_needs_secure_context');
@endphp

<div
    x-data="qrScanSequence({
        fields: @js($fields),
        sound: @js($hasSound),
        vibrate: @js($hasVibration),
        beepFrequency: @js($beepFrequency),
        beepDurationMs: @js($beepDuration),
        vibrateDurationMs: @js($vibrateDuration),
        hardwareScanner: @js($isHardwareScanner),
        burstThresholdMs: @js($burstThresholdMs),
        terminators: @js($terminators),
        minBarcodeLength: @js($minBarcodeLength),
        scanTimeoutMs: @js($scanTimeoutMs),
        suppressWhenGlobalListenerActive: @js($suppressWhenGlobal),
        fps: @js($fps),
        qrbox: @js($qrbox),
        preferRearCamera: @js($preferRear),
        formats: @js($supportedFormats),
        statePrefix: @js($statePrefix),
        componentStatePath: @js($componentStatePath),
        insecureMessage: @js($insecureMessage),
        editable: @js($isEditable)
    })"
    @qr-sequence-editable.window="editable = $event.detail.enabled"
    class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 shadow-sm"
    wire:ignore
>
    @if ($prefixWarning)
        <div class="lg:col-span-12 rounded-xl border border-warning-300 bg-warning-50 px-3 py-2 text-xs font-medium text-warning-800 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-200" role="alert">
            {{ __('filament-qr-code::ui.sequence_prefix_mismatch', ['prefix' => $prefixWarning['prefix'], 'container' => $prefixWarning['container']]) }}
        </div>
    @endif
    {{-- Left: Scanner Viewfinder & Controls --}}
    <div class="lg:col-span-5 space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :class="isScanning ? 'bg-success-500 animate-pulse' : 'bg-gray-400'"></span>
                {{ __('filament-qr-code::ui.sequence_scanner') }}
            </h4>

            <div class="flex items-center gap-2">
                <button
                    x-show="!isScanning"
                    type="button"
                    @click="startScanner()"
                    class="px-3 py-1 bg-success-600 hover:bg-success-700 text-white rounded-lg text-xs font-semibold shadow-sm"
                >
                    {{ __('filament-qr-code::ui.start') }}
                </button>
                <button
                    x-show="isScanning"
                    type="button"
                    @click="stopScanner()"
                    class="px-3 py-1 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-xs font-semibold shadow-sm"
                >
                    {{ __('filament-qr-code::ui.stop') }}
                </button>
            </div>
        </div>

        <div class="space-y-2">
            <div x-show="devices.length > 1" class="flex items-center gap-2 text-xs">
                <label class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('filament-qr-code::ui.camera') }}</label>
                <select
                    x-model="selectedDeviceId"
                    @change="isScanning ? startScanner() : null"
                    class="fi-select-input w-full rounded-md border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs text-gray-900 dark:text-white py-1 px-2"
                >
                    <template x-for="dev in devices" :key="dev.id">
                        <option :value="dev.id" x-text="dev.label || ('Camera ' + dev.id)"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="filament-qr-viewfinder">
            <div :id="elementId" class="w-full h-full"></div>

            <div x-show="isScanning" class="filament-qr-reticle">
                <div class="filament-qr-reticle-box">
                    <div class="filament-qr-reticle-corner top-left"></div>
                    <div class="filament-qr-reticle-corner top-right"></div>
                    <div class="filament-qr-reticle-corner bottom-left"></div>
                    <div class="filament-qr-reticle-corner bottom-right"></div>
                    <div class="filament-qr-laser"></div>
                </div>
            </div>

            <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 z-20">
                <div class="text-center text-white space-y-2">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-primary-500 border-t-transparent"></div>
                    <p class="text-xs">{{ __('filament-qr-code::ui.loading_camera') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Sequential Fields Checklist & Active Target --}}
    <div class="lg:col-span-7 space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ __('filament-qr-code::ui.sequential_steps') }}
            </span>
            <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                <span x-text="Object.keys(results).length"></span> / <span x-text="fields.length"></span> {{ __('filament-qr-code::ui.captured') }}
            </span>
        </div>

        <div class="space-y-2">
            <template x-for="(f, idx) in fields" :key="f.key">
                <div
                    @click="setCurrentField(idx)"
                    class="p-3 rounded-xl border transition cursor-pointer flex items-center justify-between text-sm"
                    :class="currentFieldIndex === idx
                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/30 text-primary-900 dark:text-primary-100 ring-1 ring-primary-500'
                        : (results[f.key] ? 'border-success-300 dark:border-success-800 bg-success-50/30 dark:bg-success-950/10' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900')"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold"
                            :class="results[f.key]
                                ? 'bg-success-500 text-white'
                                : (currentFieldIndex === idx ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300')"
                        >
                            <span x-show="!results[f.key]" x-text="idx + 1"></span>
                            <svg x-show="results[f.key]" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        <span class="font-medium" x-text="f.label"></span>
                    </div>

                    <div class="text-right">
                        {{-- Edited mode: every step is an input — scans fill it, operators can type or correct freely --}}
                        <template x-if="editable">
                            <input
                                type="text"
                                :value="results[f.key] || ''"
                                @input="results[f.key] = $event.target.value"
                                @change="syncEditedValue(f.key)"
                                placeholder="{{ __('filament-qr-code::ui.type_or_scan') }}"
                                class="fi-input font-mono text-xs w-44 rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1"
                            />
                        </template>

                        {{-- Unedited (locked) mode: captured values are read-only --}}
                        <template x-if="!editable">
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="font-mono text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700" x-text="results[f.key] || ''"></span>
                                <span x-show="!results[f.key] && currentFieldIndex === idx" class="text-xs font-semibold text-primary-600 dark:text-primary-400 animate-pulse">
                                    {{ __('filament-qr-code::ui.ready_to_scan') }}
                                </span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
