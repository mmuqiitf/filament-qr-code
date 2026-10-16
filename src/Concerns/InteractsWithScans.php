<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Concerns;

use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;

trait InteractsWithScans
{
    /** @return array{passes: bool, code: string, source: ?string, message: ?string, record_key: mixed} */
    #[ExposedLivewireMethod]
    public function handleScan(string $code, string $source): array
    {
        $input = ScanInput::fromClient($source);
        $code = trim($code);

        if ($this->isDisabled() || $this->isReadOnly()) {
            return ScanResult::reject($code, $input, __('filament-qr-code::ui.scan_rejected'))->toClient();
        }

        $result = $this->runScanChecks($code, $input);

        if (! $result->passes) {
            return $result->toClient();
        }

        $this->state($result->code);
        $this->callAfterStateUpdated();

        return $result->toClient();
    }

    public function runScanChecks(string $code, ?ScanInput $source): ScanResult
    {
        if ($code === '') {
            return ScanResult::reject($code, $source, __('filament-qr-code::ui.scan_empty'));
        }

        return ScanResult::accept($code, $source);
    }

    public function hasServerScanChecks(): bool
    {
        return false;
    }
}
