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

    /**
     * Intentionally a no-op: JS/CSS are registered once globally by
     * FilamentQrCodeServiceProvider::registerAssetsOnce(), so every panel,
     * table, and infolist gets them without per-panel wiring. Keeping this
     * plugin class (and its README install step) preserves backwards
     * compatibility for apps that already call ->plugin(...).
     */
    public function register(Panel $panel): void
    {
        FilamentQrCodeServiceProvider::registerAssetsOnce();
    }

    public function boot(Panel $panel): void
    {
        // Panel boot hook (assets are handled in register()).
    }
}
