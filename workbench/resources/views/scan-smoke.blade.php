<x-filament-panels::page>
    {{ $this->form }}

    <output data-testid="state" x-data x-text="JSON.stringify($wire.data)"></output>
</x-filament-panels::page>
