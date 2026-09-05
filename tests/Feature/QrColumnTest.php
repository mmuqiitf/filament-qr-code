<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;
use Mmuqiitf\FilamentQrCode\Infolists\Components\QrEntry;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

it('configures QrColumn properly and generates thumbnail data uri', function () {
    $column = QrColumn::make('barcode')
        ->thumbnailSize(40)
        ->modalSize(300)
        ->data('ITEM-555');

    expect($column->getName())->toBe('barcode')
        ->and($column->getThumbnailSize())->toBe(40)
        ->and($column->isPreviewable())->toBeTrue()
        ->and($column->isDownloadable())->toBeTrue()
        ->and($column->getThumbnailDataUri())->toStartWith('data:image/svg+xml;base64,')
        ->and($column->getModalDataUri())->toStartWith('data:image/svg+xml;base64,');
});

it('configures QrCodeDisplay properly', function () {
    $display = QrCodeDisplay::make('qr')
        ->data('DISPLAY-TEST-123')
        ->size(250)
        ->color('#ff0000');

    expect($display->getName())->toBe('qr')
        ->and($display->getQrSvgOrDataUri())->toStartWith('data:image/svg+xml;base64,');
});

it('configures QrEntry infolist entry properly', function () {
    $entry = QrEntry::make('qr_code')
        ->data('ENTRY-DATA-ABC')
        ->size(180);

    expect($entry->getName())->toBe('qr_code')
        ->and($entry->getQrDataUri())->toStartWith('data:image/svg+xml;base64,');
});
