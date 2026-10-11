<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrCodeDisplay;
use Mmuqiitf\FilamentQrCode\Infolists\Components\QrEntry;
use Mmuqiitf\FilamentQrCode\Support\QrRenderSpec;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrBulkAction;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

it('builds display and entry renders from one spec', function () {
    $display = QrCodeDisplay::make('qr')->data('SPEC-1')->size(250)->color('#ff0000');
    $entry = QrEntry::make('qr')->data('SPEC-1')->size(250)->color('#ff0000');

    expect($display->getRenderSpec()->toSignedParams('SPEC-1'))
        ->toBe($entry->getRenderSpec()->toSignedParams('SPEC-1'))
        ->and($display->getQrDataUri())->toStartWith('data:image/svg+xml;base64,');
});

it('carries logo and caption through the spec into data URIs and signed params', function () {
    $display = QrCodeDisplay::make('qr')
        ->data('SPEC-LOGO')
        ->logo('non-existent-logo.png')
        ->caption('SPEC-LOGO');

    $spec = $display->getRenderSpec();

    expect($spec->logoPath)->toBe('non-existent-logo.png')
        ->and($spec->caption)->toBe('SPEC-LOGO')
        ->and($spec->toSignedParams('SPEC-LOGO')['logo'])->toBe('non-existent-logo.png')
        ->and($spec->toSignedParams('SPEC-LOGO')['caption'])->toBe('SPEC-LOGO')
        ->and($display->getQrDataUri())->toStartWith('data:image/png;base64,');
});

it('signs the full spec on column modal URLs and reproduces it in the controller', function () {
    $column = QrColumn::make('barcode')
        ->data('LAZY-LOGO-1')
        ->logo('non-existent-logo.png');

    $url = $column->getModalUrl();

    expect($url)->toContain('logo=')
        ->and($url)->toContain('filament-qr-code/qr-image');

    $response = $this->get($url);

    $response->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('rebuilds an identical spec from signed params', function () {
    $spec = new QrRenderSpec(
        size: 250,
        margin: 4,
        foreground: '#ff0000',
        background: '#ffffff',
        format: 'svg',
        errorCorrection: 'Q',
        logoPath: 'non-existent-logo.png',
        logoSize: 60,
        caption: 'HELLO',
    );

    $rebuilt = QrRenderSpec::fromSignedParams($spec->toSignedParams('X'));

    expect($rebuilt->toSignedParams('X'))->toBe($spec->toSignedParams('X'));
});

it('keeps historic black-on-white downloads unless configured', function () {
    $spec = DownloadQrAction::make()->qrData('DL-1')->getDownloadSpec();

    expect($spec->foreground)->toBe('#000000')
        ->and($spec->background)->toBe('#ffffff')
        ->and($spec->logoPath)->toBeNull()
        ->and($spec->caption)->toBeNull();
});

it('shares one download spec between single and bulk actions', function () {
    $single = DownloadQrAction::make()->qrData('DL-2')->qrColor('#ff0000')->qrLogo('non-existent-logo.png')->qrCaption('DL-2');
    $bulk = DownloadQrBulkAction::make()->qrData('DL-2')->qrColor('#ff0000')->qrLogo('non-existent-logo.png')->qrCaption('DL-2');

    expect($single->getDownloadSpec()->toSignedParams('DL-2'))
        ->toBe($bulk->getDownloadSpec()->toSignedParams('DL-2'));

    $files = $bulk->recordsToFiles(collect([(object) ['qrData' => 'x']]));

    expect($files)->toBeArray();
});

it('labels bulk files png when a logo forces rasterization', function () {
    $bulk = DownloadQrBulkAction::make()
        ->qrData(fn ($record) => $record->code)
        ->qrLogo('non-existent-logo.png');

    $record = new class
    {
        public string $code = 'BULK-LOGO-1';
    };

    $files = $bulk->recordsToFiles(collect([$record]));

    expect(array_key_first($files))->toEndWith('.png');
});
