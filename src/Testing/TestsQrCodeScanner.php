<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Testing;

use Livewire\Features\SupportTesting\Testable;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeScannerInput;

class TestsQrCodeScanner
{
    public function __construct()
    {
        $this->registerTestMacros();
    }

    protected function registerTestMacros(): void
    {
        Testable::macro('simulateQrScan', function (string $value, ?string $field = null) {
            /** @var Testable $this */
            return $this->dispatch('qr-code-scanned', value: $value, field: $field);
        });

        Testable::macro('assertQrScannerInputExists', function (string $name) {
            /** @var Testable $this */
            return $this->assertFormComponentExists($name, function ($component) {
                return $component instanceof QrCodeScannerInput;
            });
        });

        Testable::macro('assertScanResultEquals', function (string $field, string $expectedValue) {
            /** @var Testable $this */
            return $this->assertSet("scanResults.{$field}", $expectedValue);
        });
    }
}
