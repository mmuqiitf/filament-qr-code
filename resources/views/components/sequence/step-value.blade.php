<div data-qr-part="sequence.step-value" class="fi-qr-sequence-step-value text-right">
    {{-- Edited mode: every step is an input — scans fill it, operators can type or correct freely --}}
    <template x-if="editable && !isRepeating(f.key)">
        <input
            type="text"
            :data-qr-step="f.key"
            :value="results[f.key] || ''"
            @input="results[f.key] = $event.target.value"
            @change="syncEditedValue(f.key)"
            placeholder="{{ __('filament-qr-code::ui.type_or_scan') }}"
            class="fi-input font-mono text-xs w-44 rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-2 py-1"
        />
    </template>

    {{-- Repeat steps: one chip per code, each with a remove button for mis-scans --}}
    <template x-if="isRepeating(f.key)">
        <div class="flex flex-wrap items-center justify-end gap-1">
            <template x-for="(code, codeIndex) in (Array.isArray(results[f.key]) ? results[f.key] : [])" :key="codeIndex + '-' + code">
                <span class="inline-flex items-center gap-1 font-mono text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 pl-2 pr-1 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                    <span x-text="code"></span>
                    <button
                        type="button"
                        @click.stop="removeCode(f.key, codeIndex)"
                        class="rounded px-1 text-gray-400 hover:text-danger-600"
                        :aria-label="'{{ __('filament-qr-code::ui.remove_code') }} ' + code"
                    >&times;</button>
                </span>
            </template>
        </div>
    </template>

    {{-- Unedited (locked) mode: captured values are read-only --}}
    <template x-if="!editable && !isRepeating(f.key)">
        <div class="flex items-center justify-end gap-1.5">
            <span class="font-mono text-xs text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700" x-text="Array.isArray(results[f.key]) ? results[f.key].join(', ') : (results[f.key] || '')"></span>
            <span x-show="!results[f.key] && currentFieldIndex === idx" class="text-xs font-semibold text-primary-600 dark:text-primary-400 animate-pulse">
                {{ __('filament-qr-code::ui.ready_to_scan') }}
            </span>
        </div>
    </template>
</div>
