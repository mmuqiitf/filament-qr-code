{{-- resources/views/components/embedded-scanner.blade.php --}}
@props([
    'cameraFacing' => \Mmuqiitf\FilamentQrCode\Enums\CameraFacing::Back,
    'scanMode' => \Mmuqiitf\FilamentQrCode\Enums\ScanMode::Single,
    'scanDelay' => config('qr-code.scan_delay', 1500),
    'fps' => config('qr-code.fps', 30),
    'qrboxSize' => config('qr-code.qrbox_size', 250),
    'showPreview' => config('qr-code.show_preview', true),
    'beepOnScan' => config('qr-code.beep_on_scan', true),
    'vibrateOnScan' => config('qr-code.vibrate_on_scan', true),
    'statePath' => null,
])

<div x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('qr-scanner', 'mmuqiitf/filament-qr-code') }}"
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('qr-code-styles', package: 'mmuqiitf/filament-qr-code'))]" x-data="qrScannerComponent({
        statePath: @js($statePath),
        cameraFacing: @js(is_string($cameraFacing) ? $cameraFacing : $cameraFacing->value),
        scanMode: @js(is_string($scanMode) ? $scanMode : $scanMode->value),
        scanDelay: @js($scanDelay),
        fps: @js($fps),
        qrboxSize: @js($qrboxSize),
        showPreview: @js($showPreview),
        beepOnScan: @js($beepOnScan),
        vibrateOnScan: @js($vibrateOnScan),
    })" {{ $attributes->class(['qr-scanner-embedded']) }}>
    {{-- Scanner Container with smooth transitions --}}
    <div class="relative rounded-xl overflow-hidden bg-gray-900 shadow-lg transition-all duration-300 ease-in-out aspect-video"
        :class="{ 'ring-2 ring-primary-600 dark:ring-primary-500': isScanning && !hasError && !isLoading }">
        <div :id="readerId" class="w-full h-full"></div>

        {{-- Loading State with smooth fade and backdrop blur --}}
        <div x-show="isLoading" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900/90 backdrop-blur-sm">
            <div class="text-center">
                <x-filament::loading-indicator class="h-12 w-12 text-primary-500 mx-auto mb-3" />
                <p class="text-white text-sm font-medium">
                    {{ __('filament-qr-code::messages.initializing_camera') }}
                </p>
            </div>
        </div>

        {{-- Error State with smooth fade and scale --}}
        <div x-show="hasError" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900/90 backdrop-blur-sm text-white p-6">
            <div class="text-center max-w-sm">
                <div class="mb-4 inline-flex items-center justify-center w-16 h-16 rounded-full bg-danger-500/20">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-8 w-8 text-danger-500" />
                </div>
                <p x-text="errorMessage" class="text-center text-sm leading-relaxed mb-4"></p>
                <x-filament::button x-on:click="retryCamera()" color="gray" size="sm"
                    icon="heroicon-o-arrow-path">
                    {{ __('filament-qr-code::messages.retry') }}
                </x-filament::button>
            </div>
        </div>

        {{-- Scanning Indicator with pulse animation --}}
        <div x-show="isScanning && !hasError && !isLoading" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="absolute bottom-4 left-0 right-0 flex justify-center pointer-events-none">
            <span
                class="bg-green-500 text-white text-sm font-medium px-4 py-2 rounded-full shadow-lg flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                </span>
                {{ __('filament-qr-code::messages.scanning') }}
            </span>
        </div>
    </div>

    {{-- Scanned Result Preview with smooth fade --}}
    <div x-show="showPreview && lastScannedValue" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="mt-4 p-4 bg-gray-50 dark:bg-white/5 rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20">
        <label class="text-xs font-medium uppercase tracking-wide text-gray-600 dark:text-gray-400">
            {{ __('filament-qr-code::messages.scanned_value') }}
        </label>
        <p x-text="lastScannedValue" class="mt-2 text-gray-950 dark:text-white font-mono text-sm break-all"></p>
    </div>
</div>
