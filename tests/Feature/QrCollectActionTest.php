<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

it('configures QrCollectAction camera and format options', function () {
    $action = QrCollectAction::make()
        ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128])
        ->fps(30)
        ->qrbox(280);

    expect($action->getSupportedFormats())->toBe(['QR_CODE', 'CODE_128'])
        ->and($action->getFps())->toBe(30)
        ->and($action->getQrbox())->toBe(280)
        ->and($action->isDuplicatesAllowed())->toBeFalse();
});

it('invokes the onScan callback through handleScan', function () {
    $seen = [];

    $action = QrCollectAction::make()
        ->onScan(function ($code) use (&$seen) {
            $seen[] = $code;

            return "handled-{$code}";
        });

    $result = $action->handleScan('PRD-1001');

    expect($result)->toBe('handled-PRD-1001')
        ->and($seen)->toBe(['PRD-1001']);
});

it('returns null from handleScan when no callback is registered', function () {
    expect(QrCollectAction::make()->handleScan('PRD-1001'))->toBeNull();
});
