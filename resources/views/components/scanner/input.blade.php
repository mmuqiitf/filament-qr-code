<input data-qr-part="scanner.input"
    id="{{ $id }}"
    type="text"
    x-model="value"
    {{ $isDisabled ? 'disabled' : '' }}
    {{ $applyStateBindingModifiers('wire:model') }}="{{ $statePath }}"
    placeholder="{{ $placeholder ?? __('filament-qr-code::ui.scan_placeholder') }}"
    class="fi-qr-scanner-input fi-input block w-full rounded-s-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-950 dark:text-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:opacity-70 disabled:cursor-not-allowed sm:text-sm"
/>
