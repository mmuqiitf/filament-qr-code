<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Listeners;

use Illuminate\Support\Facades\Log;
use Mmuqiitf\FilamentQrCode\Events\QrCodeScanned;

class LogQrCodeScan
{
    public function handle(QrCodeScanned $event): void
    {
        if (! (bool) config('qr-code.audit.enabled', false)) {
            return;
        }

        $channel = config('qr-code.audit.channel');

        $logger = is_string($channel) && $channel !== ''
            ? Log::channel($channel)
            : Log::driver();

        $logger->info('filament-qr-code.scan', [
            'code' => $event->code,
            'source' => $event->source,
            'field' => $event->field,
            'context' => $event->context,
        ]);
    }
}
