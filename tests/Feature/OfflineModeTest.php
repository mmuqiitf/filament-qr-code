<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\OfflineMode;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

it('defaults every camera surface to the offline banner', function (object $component): void {
    expect($component->getOfflineMode())->toBe(OfflineMode::Banner)
        ->and($component->getScanPayload()['offline'])->toBe('banner')
        ->and($component->getScanPayload()['offlineBlockedMessage'])->toBe('Scanning is blocked while this page is offline.');
})->with([
    'scanner' => fn () => QrScanner::make('sku'),
    'collector' => fn () => QrCollector::make('codes'),
    'sequence' => fn () => QrScanSequence::make(['po']),
]);

it('blocks while offline when asked, and can turn the banner off', function (): void {
    expect(QrScanner::make('sku')->blockWhenOffline()->getOfflineMode())->toBe(OfflineMode::Block)
        ->and(QrScanner::make('sku')->offline(OfflineMode::Off)->getScanPayload()['offline'])->toBe('off')
        ->and(QrScanner::make('sku')->offline('block')->getOfflineMode())->toBe(OfflineMode::Block);
});

it('lets blockWhenOffline win after offline(), and treats offline(null) as the config', function (): void {
    config(['qr-code.scan.offline' => 'off']);

    expect(QrScanner::make('sku')->offline(OfflineMode::Banner)->blockWhenOffline()->getOfflineMode())->toBe(OfflineMode::Block)
        ->and(QrScanner::make('sku')->offline(null)->getOfflineMode())->toBe(OfflineMode::Off)
        ->and(QrScanner::make('sku')->offline(OfflineMode::Off)->blockWhenOffline(false)->getOfflineMode())->toBe(OfflineMode::Off);
});

it('reads qr-code.scan.offline and ignores an unknown value', function (): void {
    config(['qr-code.scan.offline' => 'block']);

    expect(QrScanner::make('sku')->getOfflineMode())->toBe(OfflineMode::Block);

    config(['qr-code.scan.offline' => 'nope']);

    expect(QrScanner::make('sku')->getOfflineMode())->toBe(OfflineMode::Banner);
});
