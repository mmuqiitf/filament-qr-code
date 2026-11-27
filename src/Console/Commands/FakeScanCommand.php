<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Console\Commands;

use Illuminate\Console\Command;
use Mmuqiitf\FilamentQrCode\Gs1\ElementString;
use Mmuqiitf\FilamentQrCode\Support\Gs1ScanCheck;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'qr-code:fake-scan')]
class FakeScanCommand extends Command
{
    protected $signature = 'qr-code:fake-scan
        {code : The code a camera or gun would have sent}
        {--gs1 : Read the code as a GS1 label and print the rejection reason}
        {--require=* : AI every GS1 label must carry}';

    protected $description = 'Run a code through the scan checks and print the accept or reject reason';

    public function handle(): int
    {
        $code = trim((string) $this->argument('code'));

        if ($code === '') {
            $this->error((string) __('filament-qr-code::ui.scan_empty'));

            return self::FAILURE;
        }

        $require = array_values(array_filter(
            $this->option('require'),
            fn (mixed $ai): bool => is_string($ai) && $ai !== '',
        ));
        $gs1 = (bool) $this->option('gs1') || $require !== [];

        if (! $gs1) {
            $this->info((string) __('filament-qr-code::ui.fake_scan_accepted', ['code' => $code]));

            return self::SUCCESS;
        }

        $result = Gs1ScanCheck::from([], $require)->inspect($code);

        if ($result instanceof ElementString) {
            $this->info((string) __('filament-qr-code::ui.fake_scan_accepted', ['code' => $result->bracketed()]));

            return self::SUCCESS;
        }

        $this->error((string) __('filament-qr-code::ui.announce_rejected', ['reason' => $result]));

        return self::FAILURE;
    }
}
