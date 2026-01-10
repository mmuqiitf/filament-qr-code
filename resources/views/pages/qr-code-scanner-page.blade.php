<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Scanner Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('filament-qr-code::messages.scan_qr_code') }}
            </h3>

            @include('filament-qr-code::components.embedded-scanner', [
                'cameraFacing' => $cameraFacing,
                'scanMode' => $scanMode,
                'statePath' => $currentScanField,
            ])
        </div>

        {{-- Results Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                {{ __('filament-qr-code::messages.scanned_value') }}
            </h3>

            <div class="space-y-4">
                @foreach ($this->getScanFields() as $field)
                    <div
                        class="flex items-center justify-between p-3 rounded-lg
                        {{ $currentScanField === $field['key'] ? 'bg-primary-50 dark:bg-primary-900/20 ring-2 ring-primary-500' : 'bg-gray-50 dark:bg-gray-700' }}">
                        <div class="min-w-0 flex-1">
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $field['label'] }}</span>
                            <p class="font-mono text-sm text-gray-900 dark:text-white truncate">
                                {{ $scanResults[$field['key']] ?? '—' }}
                            </p>
                        </div>
                        @if (isset($scanResults[$field['key']]))
                            <button wire:click="clearScanResult('{{ $field['key'] }}')" type="button"
                                class="ml-2 text-gray-400 hover:text-danger-500">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
