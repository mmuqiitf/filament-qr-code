@php
    $statePath = $getStatePath();
    $hasSound = $hasSound();
    $hasVibration = $hasVibration();
    $isHardwareScanner = $isHardwareScannerEnabled();
    $fps = $getFps();
    $qrbox = $getQrbox();
    $allowDuplicates = $isDuplicatesAllowed();
    $delayMs = $getDelayBetweenScansMs();
    $preferRear = $isPreferRearCamera();
    $supportedFormats = $getSupportedFormats();
    $beepFrequency = $getBeepFrequencyHz();
    $beepDuration = $getBeepDurationMs();
    $vibrateDuration = $getVibrateDurationMs();
    $burstThresholdMs = $getBurstThresholdMs();
    $terminators = $getTerminators();
    $minBarcodeLength = $getMinBarcodeLength();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="qrCollector({
            state: $wire.entangle('{{ $statePath }}'),
            statePath: @js($statePath),
            allowDuplicates: @js($allowDuplicates),
            sound: @js($hasSound),
            vibrate: @js($hasVibration),
            beepFrequency: @js($beepFrequency),
            beepDurationMs: @js($beepDuration),
            vibrateDurationMs: @js($vibrateDuration),
            hardwareScanner: @js($isHardwareScanner),
            burstThresholdMs: @js($burstThresholdMs),
            terminators: @js($terminators),
            minBarcodeLength: @js($minBarcodeLength),
            fps: @js($fps),
            qrbox: @js($qrbox),
            preferRearCamera: @js($preferRear),
            formats: @js($supportedFormats),
            delayBetweenScansMs: @js($delayMs)
        })"
        class="space-y-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4"
        wire:ignore
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :class="isScanning ? 'bg-success-500 animate-pulse' : 'bg-gray-400'"></span>
                <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ __('filament-qr-code::ui.batch_qr_collector') }}</span>
                <span class="text-xs bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-300 font-bold px-2 py-0.5 rounded-full">
                    <span x-text="items.length"></span> {{ __('filament-qr-code::ui.items') }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <button
                    x-show="!isScanning"
                    type="button"
                    @click="startCollector()"
                    class="px-3 py-1.5 bg-success-600 hover:bg-success-700 text-white rounded-lg text-xs font-semibold"
                >
                    {{ __('filament-qr-code::ui.start_scanning') }}
                </button>
                <button
                    x-show="isScanning"
                    type="button"
                    @click="stopCollector()"
                    class="px-3 py-1.5 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-xs font-semibold"
                >
                    {{ __('filament-qr-code::ui.pause') }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
            <div class="md:col-span-6">
                <div class="space-y-2">
                    <div x-show="devices.length > 1" class="flex items-center gap-2 text-xs">
                        <label class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('filament-qr-code::ui.camera') }}</label>
                        <select
                            x-model="selectedDeviceId"
                            @change="isScanning ? startCollector() : null"
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
                </div>
            </div>

            <div class="md:col-span-6 space-y-2">
                <div class="flex items-center justify-between pb-1">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('filament-qr-code::ui.scanned_items_list') }}</span>
                    <button
                        x-show="items.length > 0"
                        type="button"
                        @click="clearAll()"
                        class="text-xs text-danger-600 dark:text-danger-400 hover:underline"
                    >
                        {{ __('filament-qr-code::ui.clear_all') }}
                    </button>
                </div>

                <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs font-mono border border-gray-100 dark:border-gray-700">
                            <div class="truncate mr-2">
                                <span class="font-bold text-gray-900 dark:text-white" x-text="item.code"></span>
                                <span class="text-[10px] text-gray-400 block" x-text="item.scanned_at"></span>
                            </div>
                            <button
                                type="button"
                                @click="removeItem(index)"
                                class="text-gray-400 hover:text-danger-600 p-1"
                            >
                                &times;
                            </button>
                        </div>
                    </template>
                    <div x-show="items.length === 0" class="text-center py-6 text-xs text-gray-400">
                        {{ __('filament-qr-code::ui.empty_collector') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
