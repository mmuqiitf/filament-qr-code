<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;

dataset('announcing surfaces', [
    'scanner' => [fn () => QrScanner::make('sku')->label('SKU')],
    'collector' => [fn () => QrCollector::make('codes')->label('Codes')],
    'sequence' => [fn () => QrScanSequence::make([ScanStep::make('po')->label('Purchase order')])],
]);

it('announces by default with the built-in text', function (Closure $make) {
    $payload = $make()->getScanPayload();

    expect($payload['announcements'])->toBeTrue()
        ->and($payload['customAnnouncements'])->toBeFalse();
})->with('announcing surfaces');

it('turns announcements off, also through a closure', function (Closure $make) {
    expect($make()->announcements(false)->getScanPayload()['announcements'])->toBeFalse()
        ->and($make()->announcements(fn () => false)->getScanPayload()['announcements'])->toBeFalse()
        ->and($make()->announcements()->getScanPayload()['announcements'])->toBeTrue();
})->with('announcing surfaces');

it('flags custom announcement text for the browser', function (Closure $make) {
    $payload = $make()->announceUsing(fn () => 'Hi')->getScanPayload();

    expect($payload['customAnnouncements'])->toBeTrue();
})->with('announcing surfaces');

it('passes code, accepted, reason and field to announceUsing', function () {
    $seen = null;
    $scanner = QrScanner::make('sku')->label('SKU')->announceUsing(function (string $code, bool $accepted, ?string $reason, string $field) use (&$seen) {
        $seen = compact('code', 'accepted', 'reason', 'field');

        return $accepted ? "Got {$code}" : "No: {$reason}";
    });

    expect($scanner->announceScan('A-1', true))->toBe('Got A-1')
        ->and($seen)->toBe(['code' => 'A-1', 'accepted' => true, 'reason' => null, 'field' => 'SKU'])
        ->and($scanner->announceScan('B-2', false, 'Unknown'))->toBe('No: Unknown');
});

it('returns null so the browser falls back to the default text', function () {
    expect(QrScanner::make('sku')->announceUsing(fn () => null)->announceScan('A-1', true))->toBeNull()
        ->and(QrScanner::make('sku')->announceScan('A-1', true))->toBeNull();
});

it('returns nothing when announcements are off', function () {
    $collector = QrCollector::make('codes')->announcements(false)->announceUsing(fn () => 'Hi');

    expect($collector->announceScan('A-1', true))->toBeNull();
});

it('names the step as the field for a sequence', function () {
    $sequence = QrScanSequence::make([ScanStep::make('po')->label('Purchase order')])
        ->announceUsing(fn (string $code, string $field) => "{$code} for {$field}");

    expect($sequence->announceScan('PO-1', true, null, 'po'))->toBe('PO-1 for Purchase order');
});
