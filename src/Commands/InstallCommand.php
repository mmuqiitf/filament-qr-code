<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'qr-code:install
                            {--force : Overwrite existing files}';

    protected $description = 'Install the Filament QR Code package';

    public function handle(): int
    {
        $this->info('Installing Filament QR Code...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'filament-qr-code-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('Configuration published.');

        // Publish views (optional)
        if ($this->confirm('Would you like to publish the views for customization?', false)) {
            $this->call('vendor:publish', [
                '--tag' => 'filament-qr-code-views',
                '--force' => $this->option('force'),
            ]);
            $this->info('Views published.');
        }

        // Publish translations (optional)
        if ($this->confirm('Would you like to publish the translations?', false)) {
            $this->call('vendor:publish', [
                '--tag' => 'filament-qr-code-translations',
                '--force' => $this->option('force'),
            ]);
            $this->info('Translations published.');
        }

        $this->newLine();
        $this->info('Filament QR Code has been installed successfully!');
        $this->newLine();
        $this->line('Add the plugin to your Filament panel:');
        $this->newLine();
        $this->line('  use Mmuqiitf\FilamentQrCode\QrCodePlugin;');
        $this->newLine();
        $this->line('  ->plugins([');
        $this->line('      QrCodePlugin::make(),');
        $this->line('  ])');
        $this->newLine();

        return self::SUCCESS;
    }
}
