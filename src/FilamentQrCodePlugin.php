<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentQrCodePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'filament-qr-code';
    }

    public function register(Panel $panel): void
    {
        // Panel registration hook
    }

    public function boot(Panel $panel): void
    {
        // Panel boot hook
    }
}
