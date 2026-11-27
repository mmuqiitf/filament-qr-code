<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Tests\TestCase;

uses(TestCase::class)->in('Feature');

// These Unit/Docs files call into package code that resolves the container
// (`config()`, facades, cache). Everything else under Unit/Docs/Tooling is
// pure and skips Testbench so the suite does not boot Laravel for it.
uses(TestCase::class)->in(
    'Docs/FacadeDocumentedTest.php',
    'Unit/BarcodeFacadeTest.php',
    'Unit/BarcodeServiceTest.php',
    'Unit/CheckDigitTest.php',
    'Unit/DigitalLinkTest.php',
    'Unit/HardwareScannerBufferTest.php',
    'Unit/LabelSheetTest.php',
    'Unit/LogoPathGuardTest.php',
    'Unit/QrCodeServiceTest.php',
    'Unit/QrGoldenOutputTest.php',
    'Unit/QrLogoCompositorTest.php',
    'Unit/QrRenderCacheFlushTest.php',
    'Unit/QrRenderCacheTtlTest.php',
    'Unit/ScanCooldownTest.php',
);
