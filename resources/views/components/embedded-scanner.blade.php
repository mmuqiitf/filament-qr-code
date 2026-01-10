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

<div x-data="qrScanner({
    statePath: @js($statePath),
    cameraFacing: @js(is_string($cameraFacing) ? $cameraFacing : $cameraFacing->value),
    scanMode: @js(is_string($scanMode) ? $scanMode : $scanMode->value),
    scanDelay: @js($scanDelay),
    fps: @js($fps),
    qrboxSize: @js($qrboxSize),
    showPreview: @js($showPreview),
    beepOnScan: @js($beepOnScan),
    vibrateOnScan: @js($vibrateOnScan),
})" x-init="init()" {{ $attributes->class(['qr-scanner-embedded']) }}>
    {{-- Scanner Container --}}
    <div class="relative rounded-lg overflow-hidden bg-black aspect-video">
        <div :id="readerId" class="w-full h-full"></div>

        {{-- Loading State --}}
        <div x-show="isLoading" x-cloak class="absolute inset-0 flex items-center justify-center bg-gray-900/80">
            <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>

        {{-- Error State --}}
        <div x-show="hasError" x-cloak
            class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900/80 text-white p-4">
            <svg class="h-12 w-12 text-danger-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <p x-text="errorMessage" class="text-center text-sm"></p>
            <button x-on:click="retryCamera()" type="button"
                class="mt-4 inline-flex items-center gap-1 justify-center rounded-lg border border-transparent bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500">
                {{ __('filament-qr-code::messages.retry') }}
            </button>
        </div>

        {{-- Scanning Indicator --}}
        <div x-show="isScanning && !hasError && !isLoading" x-cloak
            class="absolute bottom-4 left-0 right-0 flex justify-center">
            <span class="bg-green-500 text-white text-sm px-3 py-1 rounded-full animate-pulse">
                {{ __('filament-qr-code::messages.scanning') }}
            </span>
        </div>
    </div>

    {{-- Scanned Result Preview --}}
    <div x-show="showPreview && lastScannedValue" x-cloak class="mt-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ __('filament-qr-code::messages.scanned_value') }}
        </label>
        <p x-text="lastScannedValue" class="mt-1 text-gray-900 dark:text-white font-mono text-sm break-all"></p>
    </div>
</div>
