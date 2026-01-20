<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Scanner Section --}}
        <div class="lg:col-span-12 xl:col-span-8">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-qr-code" class="h-5 w-5 text-primary-500" />
                        <span>{{ __('filament-qr-code::messages.scan_qr_code') }}</span>
                    </div>
                </x-slot>

                <x-slot name="headerEnd">
                    <div class="flex items-center gap-4">
                        {{-- Camera Selector --}}
                        @if ($showCameraSelector && count($availableCameras) > 0)
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 hidden sm:inline">
                                    {{ __('filament-qr-code::messages.select_camera') }}:
                                </span>
                                <x-filament::input.wrapper size="sm">
                                    <x-filament::input.select wire:model.live="selectedCameraId"
                                        wire:change="selectCamera($event.target.value)" class="text-xs">
                                        @foreach ($availableCameras as $camera)
                                            <option value="{{ $camera['id'] }}">{{ $camera['label'] }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </div>
                        @endif

                        {{-- FPS Control --}}
                        @if ($showFpsControl)
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 hidden sm:inline">
                                    {{ __('filament-qr-code::messages.fps') }}:
                                </span>
                                <x-filament::input.wrapper size="sm" class="w-16">
                                    <x-filament::input type="number" wire:model.live="fps"
                                        wire:change="updateFps($event.target.value)" min="5" max="60"
                                        step="5" class="text-xs text-center" />
                                </x-filament::input.wrapper>
                            </div>
                        @endif
                    </div>
                </x-slot>

                <div class="space-y-6">
                    @include('filament-qr-code::components.embedded-scanner', [
                        'cameraFacing' => $cameraFacing,
                        'scanMode' => $scanMode,
                        'statePath' => $currentScanField,
                        'fps' => $fps,
                    ])
                </div>
            </x-filament::section>
        </div>

        {{-- Results Section --}}
        <div class="lg:col-span-12 xl:col-span-4">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-list-bullet" class="h-5 w-5 text-primary-500" />
                        <span>{{ __('filament-qr-code::messages.scanned_value') }}</span>
                    </div>
                </x-slot>

                <div class="space-y-4">
                    @forelse ($this->getScanFields() as $field)
                        <div
                            class="relative p-4 rounded-xl border transition-all duration-300
                            {{ $currentScanField === $field['key']
                                ? 'bg-primary-50 dark:bg-primary-500/5 border-primary-500 ring-2 ring-primary-500/20'
                                : 'bg-gray-50 dark:bg-white/5 border-gray-200 dark:border-white/10' }}">

                            <div class="flex items-start justify-between">
                                <div class="min-w-0 flex-1">
                                    <label
                                        class="block text-[10px] font-bold uppercase tracking-wider {{ $currentScanField === $field['key'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $field['label'] }}
                                    </label>

                                    <div class="mt-1 flex items-center gap-2">
                                        @if (isset($scanResults[$field['key']]))
                                            <x-filament::icon icon="heroicon-m-check-circle"
                                                class="h-4 w-4 text-success-500" />
                                            <span class="font-mono text-sm text-gray-900 dark:text-white break-all">
                                                {{ $scanResults[$field['key']] }}
                                            </span>
                                        @else
                                            <div
                                                class="flex items-center gap-2 text-gray-400 dark:text-gray-600 italic text-sm">
                                                @if ($currentScanField === $field['key'])
                                                    <x-filament::loading-indicator class="h-3 w-3" />
                                                    <span>{{ __('filament-qr-code::messages.scanning') }}...</span>
                                                @else
                                                    <span>—</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if (isset($scanResults[$field['key']]))
                                    <x-filament::icon-button icon="heroicon-o-x-mark" color="danger" size="sm"
                                        wire:click="clearScanResult('{{ $field['key'] }}')"
                                        tooltip="{{ __('filament-qr-code::messages.clear') }}" />
                                @endif
                            </div>

                            @if ($currentScanField === $field['key'])
                                <div
                                    class="absolute -left-1 top-1/2 -translate-y-1/2 w-1 h-8 bg-primary-500 rounded-full">
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center p-6 text-gray-500 dark:text-gray-400 italic">
                            No scan fields defined.
                        </div>
                    @endforelse
                </div>

                <x-slot name="footer">
                    <div class="flex justify-end gap-3">
                        <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="resetScanSequence">
                            {{ __('filament-qr-code::messages.reset') }}
                        </x-filament::button>
                    </div>
                </x-slot>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
