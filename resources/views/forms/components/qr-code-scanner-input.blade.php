@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <x-filament::input.wrapper :disabled="$isDisabled()" :inline-prefix="$isPrefixInline()" :inline-suffix="$isSuffixInline()" :prefix="$getPrefixLabel()" :prefix-actions="$getPrefixActions()"
        :prefix-icon="$getPrefixIcon()" :prefix-icon-color="$getPrefixIconColor()" :suffix="$getSuffixLabel()" :suffix-actions="$getSuffixActions()" :suffix-icon="$getSuffixIcon()" :suffix-icon-color="$getSuffixIconColor()"
        :valid="!$errors->has($statePath)" :attributes="\Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())->class([
            'fi-fo-text-input overflow-hidden',
        ])">
        <x-filament::input :attributes="\Filament\Support\prepare_inherited_attributes($getExtraInputAttributeBag())->merge(
            [
                'autocapitalize' => $getAutocapitalize(),
                'autocomplete' => $getAutocomplete(),
                'autofocus' => $isAutofocused(),
                'disabled' => $isDisabled(),
                'id' => $getId(),
                'inputmode' => $getInputMode(),
                'list' => $getDatalistOptions() ? $getId() . '-list' : null,
                'maxlength' => !$isConcealed() ? $getMaxLength() : null,
                'minlength' => !$isConcealed() ? $getMinLength() : null,
                'placeholder' => $getPlaceholder(),
                'readonly' => $isReadOnly(),
                'required' => $isRequired() && !$isConcealed(),
                'step' => $getStep(),
                'type' => blank($getMask()) ? $getType() : 'text',
                $applyStateBindingModifiers('wire:model') => $statePath,
            ],
            escape: false,
        )" />
    </x-filament::input.wrapper>

    @if ($datalistOptions = $getDatalistOptions())
        <datalist id="{{ $getId() }}-list">
            @foreach ($datalistOptions as $option)
                <option value="{{ $option }}" />
            @endforeach
        </datalist>
    @endif
</x-dynamic-component>
