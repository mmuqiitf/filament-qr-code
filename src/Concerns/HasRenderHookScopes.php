<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Throwable;

trait HasRenderHookScopes
{
    /**
     * Scopes for this field's render hooks: the Livewire component class
     * and the field's state path.
     *
     * @return list<string>
     */
    public function getRenderHookScopes(): array
    {
        $scopes = [];

        try {
            $livewire = $this->getLivewire();
            $scopes[] = $livewire::class;
        } catch (Throwable) {
            // Rendered outside Livewire.
        }

        $statePath = $this->getStatePath();

        if (is_string($statePath) && $statePath !== '') {
            $scopes[] = $statePath;
        }

        return $scopes;
    }
}
