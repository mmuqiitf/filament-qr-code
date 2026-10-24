<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Listeners\LogQrCodeScanListener;
use Symfony\Component\Finder\Finder;

/**
 * Every API removed in 1.0: the pattern that must be gone from the code and docs.
 *
 * @return array<string, string>
 */
function removedApis(): array
{
    return [
        'filament-qr-code.image' => '/filament-qr-code\.image/',
        'suppressWhenGlobalListener' => '/suppressWhenGlobal/',
        'isGlobalHardwareListenerActive' => '/isGlobalHardwareListenerActive/',
        'statePathPrefix' => '/statePathPrefix|prefixMismatchWarning|sequence_prefix_mismatch/',
        'LogQrCodeScan' => '/LogQrCodeScan\b/',
    ];
}

/** @return list<string> */
function shippedFiles(): array
{
    $root = dirname(__DIR__, 2);
    $finder = Finder::create()->files()
        ->in(["{$root}/src", "{$root}/config", "{$root}/resources/js", "{$root}/resources/views", "{$root}/resources/lang", "{$root}/docs"])
        ->exclude('superpowers')
        ->notName('*.test.js');

    return [...array_map(fn ($file): string => $file->getRealPath(), iterator_to_array($finder, false)), "{$root}/README.md"];
}

it('removed APIs are gone; UPGRADE.md lists each', function (string $api, string $pattern): void {
    $leftovers = array_values(array_filter(shippedFiles(), fn (string $path): bool => preg_match($pattern, (string) file_get_contents($path)) === 1));

    $upgrade = (string) file_get_contents(dirname(__DIR__, 2).'/UPGRADE.md');
    $section = (string) strstr($upgrade, '## From 0.6 to 1.0');

    expect($leftovers)->toBe([])
        ->and($section)->toContain($api);
})->with(fn (): array => array_map(null, array_keys(removedApis()), array_values(removedApis())));

it('removed APIs no longer resolve at runtime', function (): void {
    expect(Route::has('filament-qr-code.image'))->toBeFalse()
        ->and(method_exists(QrScanner::class, 'suppressWhenGlobalListener'))->toBeFalse()
        ->and(method_exists(QrScanSequence::class, 'statePathPrefix'))->toBeFalse()
        ->and(class_exists('Mmuqiitf\\FilamentQrCode\\Listeners\\LogQrCodeScan'))->toBeFalse()
        ->and(class_exists(LogQrCodeScanListener::class))->toBeTrue()
        ->and(QrScanner::make('sku')->getScanPayload())->not->toHaveKey('suppressWhenGlobalListenerActive');
});

it('needs three fast keys for a hardware burst by default', function (): void {
    expect(config('qr-code.hardware_scanner.min_fast_keys'))->toBe(3)
        ->and(QrScanner::make('sku')->getScanPayload()['minFastKeys'])->toBe(3);

    config()->set('qr-code.hardware_scanner', []);
    expect(QrScanner::make('sku')->getScanPayload()['minFastKeys'])->toBe(3);

    $upgrade = (string) strstr((string) file_get_contents(dirname(__DIR__, 2).'/UPGRADE.md'), '## From 0.6 to 1.0');
    expect($upgrade)->toContain('min_fast_keys');
});

it('upgrade guide lists the behaviour changes', function (): void {
    $upgrade = (string) strstr((string) file_get_contents(dirname(__DIR__, 2).'/UPGRADE.md'), '## From 0.6 to 1.0');

    expect($upgrade)->toContain('validateAllUsing')->toContain('pipe(');
});
