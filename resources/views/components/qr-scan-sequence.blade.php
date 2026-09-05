@php
    $fields = $getScanFields();
    $hasSound = $hasSound();
    $hasVibration = $hasVibration();
    $isHardwareScanner = $isHardwareScannerEnabled();
    $fps = $getFps();
    $qrbox = $getQrbox();
@endphp

<div
    x-data="qrScanSequence({
        fields: @js($fields),
        sound: @js($hasSound),
        vibrate: @js($hasVibration),
        hardwareScanner: @js($isHardwareScanner),
        fps: @js($fps),
        qrbox: @js($qrbox)
    })"
    class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-4 shadow-sm"
>
    {{-- Left: Scanner Viewfinder & Controls --}}
    <div class="lg:col-span-5 space-y-4">
        <div class="flex items-center justify-between">
            <h4 class="font-semibold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full" :class="isScanning ? 'bg-success-500 animate-pulse' : 'bg-gray-400'"></span>
                {{ __('QR Sequence Scanner') }}
            </h4>

            <div class="flex items-center gap-2">
                <button
                    x-show="!isScanning"
                    type="button"
                    @click="startScanner()"
                    class="px-3 py-1 bg-success-600 hover:bg-success-700 text-white rounded-lg text-xs font-semibold shadow-sm"
                >
                    {{ __('Start') }}
                </button>
                <button
                    x-show="isScanning"
                    type="button"
                    @click="stopScanner()"
                    class="px-3 py-1 bg-danger-600 hover:bg-danger-700 text-white rounded-lg text-xs font-semibold shadow-sm"
                >
                    {{ __('Stop') }}
                </button>
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
                    <p class="text-xs">{{ __('Loading camera...') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Sequential Fields Checklist & Active Target --}}
    <div class="lg:col-span-7 space-y-3">
        <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                {{ __('Sequential Steps') }}
            </span>
            <span class="text-xs font-medium text-primary-600 dark:text-primary-400">
                <span x-text="Object.keys(results).length"></span> / <span x-text="fields.length"></span> {{ __('Captured') }}
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
                        <span x-show="results[f.key]" class="font-mono text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700" x-text="results[f.key]"></span>
                        <span x-show="!results[f.key] && currentFieldIndex === idx" class="text-xs font-semibold text-primary-600 dark:text-primary-400 animate-pulse">
                            {{ __('Ready to scan') }}
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
