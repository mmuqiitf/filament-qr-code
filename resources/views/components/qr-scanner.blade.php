@php
    $statePath = $getStatePath();
    $id = $getId();
    $isDisabled = $isDisabled();
    $nextField = $getNextField();
    $hasSound = $hasSound();
    $hasVibration = $hasVibration();
    $isHardwareScanner = $isHardwareScannerEnabled();
    $burstThresholdMs = $getBurstThresholdMs();
    $fps = $getFps();
    $qrbox = $getQrbox();
    $preferRear = $isPreferRearCamera();
    $allowUpload = $isUploadAllowed();
    $supportedFormats = $getSupportedFormats();
    $placeholder = $getPlaceholder();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="qrScanner({
            state: $wire.entangle('{{ $statePath }}'),
            nextField: @js($nextField),
            sound: @js($hasSound),
            vibrate: @js($hasVibration),
            hardwareScanner: @js($isHardwareScanner),
            burstThresholdMs: @js($burstThresholdMs),
            fps: @js($fps),
            qrbox: @js($qrbox),
            preferRearCamera: @js($preferRear),
            formats: @js($supportedFormats)
        })"
        data-field-name="{{ $getName() }}"
        class="relative"
    >
        <div class="flex rounded-lg shadow-sm">
            <input
                id="{{ $id }}"
                type="text"
                x-model="value"
                {{ $isDisabled ? 'disabled' : '' }}
                {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
                placeholder="{{ $placeholder ?? __('Scan or enter code...') }}"
                class="fi-input block w-full rounded-s-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-950 dark:text-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:opacity-70 disabled:cursor-not-allowed sm:text-sm"
            />

            <button
                type="button"
                @click="openScannerModal()"
                {{ $isDisabled ? 'disabled' : '' }}
                class="inline-flex items-center gap-x-1.5 rounded-e-lg border border-s-0 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3.5 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                title="{{ __('Scan QR with camera') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-primary-600 dark:text-primary-400">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM14.625 3.75c-.621 0-1.125.504-1.125 1.125v4.5c0 .621.504 1.125 1.125 1.125h4.5c.621 0 1.125-.504 1.125-1.125v-4.5c0-.621-.504-1.125-1.125-1.125h-4.5ZM17.25 17.25h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm-3-3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm3-6h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Zm6 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                <span class="hidden sm:inline">{{ __('Scan') }}</span>
            </button>
        </div>

        {{-- Scanner Modal --}}
        <div
            x-show="isModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/70 backdrop-blur-sm"
            @keydown.escape.window="closeScannerModal()"
        >
            <div
                @click.outside="closeScannerModal()"
                class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-800"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="flex h-3 w-3 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                        </span>
                        <h3 class="font-semibold text-gray-900 dark:text-white text-sm">
                            {{ __('Scan QR Code') }}
                        </h3>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            x-show="hasTorch"
                            type="button"
                            @click="toggleTorch()"
                            class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white rounded-lg transition"
                            :class="{ 'text-amber-500 dark:text-amber-400': torchActive }"
                            title="{{ __('Toggle Flashlight') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="closeScannerModal()"
                            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Camera Feed Container --}}
                <div class="p-4 space-y-3">
                    {{-- Camera device selector --}}
                    <div x-show="devices.length > 1" class="flex items-center gap-2 text-xs">
                        <label class="text-gray-500 dark:text-gray-400 shrink-0">{{ __('Camera:') }}</label>
                        <select
                            x-model="selectedDeviceId"
                            @change="startScan()"
                            class="fi-select-input w-full rounded-md border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-xs text-gray-900 dark:text-white py-1 px-2"
                        >
                            <template x-for="dev in devices" :key="dev.id">
                                <option :value="dev.id" x-text="dev.label || ('Camera ' + dev.id)"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Viewfinder --}}
                    <div class="filament-qr-viewfinder">
                        <div :id="scannerElementId" class="w-full h-full"></div>

                        {{-- Reticle Box & Laser --}}
                        <div x-show="isScanning" class="filament-qr-reticle">
                            <div class="filament-qr-reticle-box">
                                <div class="filament-qr-reticle-corner top-left"></div>
                                <div class="filament-qr-reticle-corner top-right"></div>
                                <div class="filament-qr-reticle-corner bottom-left"></div>
                                <div class="filament-qr-reticle-corner bottom-right"></div>
                                <div class="filament-qr-laser"></div>
                            </div>
                        </div>

                        {{-- Loading Spinner --}}
                        <div x-show="isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 z-20">
                            <div class="text-center text-white space-y-2">
                                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent"></div>
                                <p class="text-xs">{{ __('Starting camera feed...') }}</p>
                            </div>
                        </div>

                        {{-- Error Message --}}
                        <div x-show="hasError" class="absolute inset-0 flex items-center justify-center bg-gray-950/90 z-20 p-4">
                            <div class="text-center text-danger-400 space-y-3 max-w-xs">
                                <svg class="w-8 h-8 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-xs font-medium" x-text="errorMessage"></p>
                                <button
                                    type="button"
                                    @click="loadCamerasAndStart()"
                                    class="px-3 py-1.5 text-xs font-medium bg-primary-600 hover:bg-primary-700 text-white rounded-md"
                                >
                                    {{ __('Retry') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Upload image fallback --}}
                    @if ($allowUpload)
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <label class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                <span>{{ __('Scan from image file') }}</span>
                                <input type="file" accept="image/*" class="hidden" @change="scanFile($event)">
                            </label>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
