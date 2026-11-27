<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures\Concerns;

use Filament\Forms\Concerns\InteractsWithForms;

/**
 * Shared Livewire form host for package tests: state path `data`, fill on
 * mount, a default form submit, schema key lookup, and a one-div render.
 */
trait InteractsWithTestForm
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public bool $submitted = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function submit(): void
    {
        $this->form->getState();
        $this->submitted = true;
    }

    public function keyOf(string $name): string
    {
        return $this->form->getComponent(
            fn ($component): bool => method_exists($component, 'getName') && $component->getName() === $name,
        )->getKey();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
