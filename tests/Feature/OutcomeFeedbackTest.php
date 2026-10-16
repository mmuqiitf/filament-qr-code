<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;

it('resolves success and error feedback from config defaults', function (): void {
    expect(QrScanner::make('sku')->getScanPayload())
        ->toHaveKey('successFeedback', ['sound' => true, 'vibrate' => [100], 'flash' => true])
        ->toHaveKey('errorFeedback', ['sound' => true, 'vibrate' => [80, 60, 80], 'flash' => true]);
});

it('reads overrides from feedback.success and feedback.error config', function (): void {
    config()->set('qr-code.feedback.success', ['sound' => false, 'vibrate' => [50], 'flash' => false]);
    config()->set('qr-code.feedback.error', ['vibrate' => 'not-a-pattern']);

    $scanner = QrScanner::make('sku');

    expect($scanner->getSuccessFeedback())->toBe(['sound' => false, 'vibrate' => [50], 'flash' => false])
        ->and($scanner->getErrorFeedback())->toBe(['sound' => true, 'vibrate' => [80, 60, 80], 'flash' => true]);
});

it('lets each field override beep, vibration patterns and flash', function (): void {
    $scanner = QrScanner::make('sku')
        ->beep(success: false, error: true)
        ->vibrate(success: [30], error: [200, 100, 200])
        ->flash(false);

    expect($scanner->getSuccessFeedback())->toBe(['sound' => false, 'vibrate' => [30], 'flash' => false])
        ->and($scanner->getErrorFeedback())->toBe(['sound' => true, 'vibrate' => [200, 100, 200], 'flash' => false]);
});

it('keeps sound(false) and vibrate(false) as master switches', function (): void {
    $scanner = QrScanner::make('sku')->sound(false)->vibrate(false);

    expect($scanner->getSuccessFeedback())->toBe(['sound' => false, 'vibrate' => [], 'flash' => true])
        ->and($scanner->getErrorFeedback())->toBe(['sound' => false, 'vibrate' => [], 'flash' => true]);
});

it('only adds outcome feedback to the scanner payload', function (): void {
    expect(QrScanSequence::make(['a'])->getScanPayload())
        ->not->toHaveKey('successFeedback');
});
