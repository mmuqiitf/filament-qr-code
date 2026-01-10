<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Mmuqiitf\FilamentQrCode\Pages\QrCodeScannerPage;

class QrCodePlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool $hasQrCodeScannerPage = false;

    protected array $pages = [];

    /**
     * Fluent instantiation - uses container for potential swapping
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Get the plugin instance from a panel
     */
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
     * Enable the built-in QR Code Scanner Page
     */
    public function qrCodeScannerPage(bool $condition = true): static
    {
        $this->hasQrCodeScannerPage = $condition;

        return $this;
    }

    public function hasQrCodeScannerPage(): bool
    {
        return $this->hasQrCodeScannerPage;
    }

    /**
     * Register additional custom pages
     */
    public function pages(array $pages): static
    {
        $this->pages = array_merge($this->pages, $pages);

        return $this;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * Called when plugin is registered to a panel
     */
    public function register(Panel $panel): void
    {
        $pagesToRegister = $this->pages;

        if ($this->hasQrCodeScannerPage) {
            $pagesToRegister[] = QrCodeScannerPage::class;
        }

        if (filled($pagesToRegister)) {
            $panel->pages($pagesToRegister);
        }
    }

    /**
     * Called when the panel boots (via middleware)
     */
    public function boot(Panel $panel): void
    {
        //
    }
}
