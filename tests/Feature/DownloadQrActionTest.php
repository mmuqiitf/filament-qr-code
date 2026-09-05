<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\QrCollectAction;

it('configures DownloadQrAction properly', function () {
    $action = DownloadQrAction::make()
        ->qrData('ACTION-DOWNLOAD-123')
        ->qrFileName('test-export')
        ->qrImageSize(500)
        ->qrMargin(3)
        ->qrFormat(QrFormat::Png);

    expect($action->getName())->toBe('download_qr')
        ->and($action->getQrData(null))->toBe('ACTION-DOWNLOAD-123')
        ->and($action->getQrFileName(null))->toBe('test-export');
});

it('configures QrCollectAction properly', function () {
    $action = QrCollectAction::make()
        ->allowDuplicates(false)
        ->sound(true)
        ->vibrate(true);

    expect($action->getName())->toBe('qr_collect')
        ->and($action->isDuplicatesAllowed())->toBeFalse()
        ->and($action->hasSound())->toBeTrue()
        ->and($action->hasVibration())->toBeTrue();
});
