<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

it('respects format and margin settings in the modal preview', function () {
    $column = QrColumn::make('barcode')
        ->data('ITEM-777')
        ->format(QrFormat::Png)
        ->margin(4);

    expect($column->getModalDataUri())->toStartWith('data:image/png;base64,')
        ->and($column->getThumbnailDataUri())->toStartWith('data:image/png;base64,');
});

it('caches repeated data uri generation for the same payload', function () {
    QrColumn::flushDataUriCache();

    $column = QrColumn::make('barcode')->data('CACHED-ITEM-1');

    $first = $column->getThumbnailDataUri();
    $second = $column->getThumbnailDataUri();

    expect($first)->toBe($second)->and($first)->not->toBe('');

    QrColumn::flushDataUriCache();
});
