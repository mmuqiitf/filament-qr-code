<x-filament-panels::page>
    {{ $this->form }}

    <output data-testid="collector-state">{{ json_encode($data['codes'] ?? []) }}</output>
</x-filament-panels::page>
