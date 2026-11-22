{{-- Errors a submit left on the steps. Outside wire:ignore, so Livewire morphs it. --}}
@php($stepErrors = $getStepErrors())
@if ($stepErrors !== [])
    <ul data-qr-part="sequence.errors" role="alert" class="fi-qr-sequence-errors mt-2 space-y-1 text-sm text-danger-600 dark:text-danger-400">
        @foreach ($stepErrors as $error)
            <li data-step="{{ $error['step'] }}"><span class="font-medium">{{ $error['label'] }}:</span> {{ $error['message'] }}</li>
        @endforeach
    </ul>
@endif
