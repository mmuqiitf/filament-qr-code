<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <button type="submit">Receive</button>
    </form>

    <output data-testid="submitted" x-data x-text="$wire.submitted"></output>
</x-filament-panels::page>
