<x-filament-panels::page>
    {{-- Camera Controls Bar - Only show when cameras are detected --}}
    @if (count($availableCameras) > 0 && ($showCameraSelector || $showFpsControl))
        <div class="mb-6">
            <div
                class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content p-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        {{-- Camera Selector --}}
                        @if ($showCameraSelector && count($availableCameras) > 1)
                            <div class="flex-1 min-w-[200px]">
                                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                        {{ __('filament-qr-code::messages.select_camera') }}
                                    </span>
                                </label>
                                <select wire:model.live="selectedCameraId" wire:change="selectCamera($event.target.value)"
                                    class="fi-select-input block w-full border-none bg-white py-1.5 pe-8 ps-3 text-base text-gray-950 transition duration-75 focus:ring-2 focus:ring-primary-600 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] dark:bg-white/5 dark:text-white dark:focus:ring-primary-500 sm:text-sm sm:leading-6 [&_optgroup]:bg-white [&_optgroup]:dark:bg-gray-900 [&_option]:bg-white [&_option]:dark:bg-gray-900 rounded-lg shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20">
                                    @foreach ($availableCameras as $camera)
                                        <option value="{{ $camera['id'] }}">{{ $camera['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- FPS Control --}}
                        @if ($showFpsControl)
                            <div class="w-32">
                                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                                    <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                        {{ __('filament-qr-code::messages.fps') }}
                                    </span>
                                </label>
                                <input type="number" wire:model.live="fps" wire:change="updateFps($event.target.value)"
                                    min="5" max="60" step="5"
                                    class="fi-input block w-full border-none py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:focus:ring-primary-500 sm:text-sm sm:leading-6 bg-white dark:bg-white/5 rounded-lg shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20 px-3">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Scanner Section --}}
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3
                        class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        {{ __('filament-qr-code::messages.scan_qr_code') }}
                    </h3>
                    @if (count($availableCameras) === 1)
                        <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                            </svg>
                            {{ $availableCameras[0]['label'] ?? 'Camera' }}
                        </span>
                    @endif
                </div>

                @include('filament-qr-code::components.embedded-scanner', [
                    'cameraFacing' => $cameraFacing,
                    'scanMode' => $scanMode,
                    'statePath' => $currentScanField,
                    'fps' => $fps,
                ])
            </div>
        </div>

        {{-- Results Section --}}
        <div
            class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <h3
                    class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4">
                    {{ __('filament-qr-code::messages.scanned_value') }}
                </h3>

                <div class="space-y-3">
                    @foreach ($this->getScanFields() as $field)
                        <div
                            class="flex items-center justify-between p-4 rounded-lg transition-all duration-200
                            {{ $currentScanField === $field['key'] ? 'bg-primary-50 dark:bg-primary-500/10 ring-2 ring-primary-600 dark:ring-primary-500' : 'bg-gray-50 dark:bg-white/5 hover:bg-gray-100 dark:hover:bg-white/10' }}">
                            <div class="min-w-0 flex-1">
                                <span
                                    class="text-xs font-medium uppercase tracking-wide {{ $currentScanField === $field['key'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $field['label'] }}
                                </span>
                                <p class="mt-1 font-mono text-sm text-gray-950 dark:text-white truncate">
                                    {{ $scanResults[$field['key']] ?? '—' }}
                                </p>
                            </div>
                            @if (isset($scanResults[$field['key']]))
                                <button wire:click="clearScanResult('{{ $field['key'] }}')" type="button"
                                    class="ml-3 p-1.5 text-gray-400 hover:text-danger-600 dark:hover:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-500/10 rounded-lg transition-colors duration-200"
                                    title="{{ __('filament-qr-code::messages.clear') }}">
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
