<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Mmuqiitf\FilamentQrCode\Support\QrZipArchive;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;

it('packs rendered QR binaries into a readable ZIP', function () {
    $files = [
        'qr-1.svg' => '<svg>one</svg>',
        'qr-2.svg' => '<svg>two</svg>',
    ];

    $archive = QrZipArchive::fromFiles($files);

    expect(str_starts_with($archive, "PK\x03\x04"))->toBeTrue();

    $path = tempnam(sys_get_temp_dir(), 'qr-test-');
    file_put_contents($path, $archive);

    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();

    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
    }

    expect($names)->toBe(['qr-1.svg', 'qr-2.svg'])
        ->and($zip->getFromName('qr-2.svg'))->toBe('<svg>two</svg>');

    $zip->close();
    unlink($path);
});

it('resolves bulk files per record for ZIP export', function () {
    $action = DownloadQrBulkAction::make()
        ->qrData(fn ($record) => $record['sku'])
        ->qrFileName(fn ($record) => $record['sku']);

    $files = $action->recordsToFiles(new Collection([
        ['sku' => 'BULK-1'],
        ['sku' => ''],
        ['sku' => 'BULK-2'],
    ]));

    expect(array_keys($files))->toBe(['BULK-1.svg', 'BULK-2.svg'])
        ->and($action->getName())->toBe('download_qr_bulk');
});
