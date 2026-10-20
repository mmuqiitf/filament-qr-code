<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Closure;
use Mmuqiitf\FilamentQrCode\Enums\OptionsSync;

trait HasOptionsSync
{
    protected OptionsSync|string|Closure|null $optionsSync = null;

    /**
     * Morph (default): option changes reach the browser on every Livewire
     * re-render. Refresh: only on a qr-scanner-refresh / qr-sequence-refresh event.
     */
    public function syncOptions(OptionsSync|string|Closure|null $sync): static
    {
        $this->optionsSync = $sync;

        return $this;
    }

    public function getOptionsSync(): OptionsSync
    {
        $sync = $this->evaluate($this->optionsSync)
            ?? (function_exists('config') ? config('qr-code.scan.sync_options') : null);

        if ($sync instanceof OptionsSync) {
            return $sync;
        }

        return is_string($sync) ? (OptionsSync::tryFrom($sync) ?? OptionsSync::Morph) : OptionsSync::Morph;
    }

    /** @return array{syncOptions: string} */
    public function getOptionsSyncScanPayload(): array
    {
        return ['syncOptions' => $this->getOptionsSync()->value];
    }
}
