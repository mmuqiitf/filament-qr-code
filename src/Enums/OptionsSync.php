<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Enums;

/**
 * When the browser picks up options changed on the server: on every
 * Livewire re-render (Morph) or only on an explicit refresh event (Refresh).
 */
enum OptionsSync: string
{
    case Morph = 'morph';
    case Refresh = 'refresh';
}
