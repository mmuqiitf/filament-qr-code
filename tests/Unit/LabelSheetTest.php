<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Gs1\DigitalLink;
use Mmuqiitf\FilamentQrCode\Printing\Label;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheet;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheetRenderer;

it('defines Avery 5160 as a 3-by-10 letter sheet', function (): void {
    $sheet = LabelSheet::avery5160();

    expect($sheet->pageSize)->toBe('letter')
        ->and($sheet->columns)->toBe(3)
        ->and($sheet->rows)->toBe(10)
        ->and($sheet->labelsPerPage())->toBe(30)
        ->and($sheet->labelWidth)->toBe('66.675mm')
        ->and($sheet->labelHeight)->toBe('25.4mm');
});

it('defines Avery 5163 and L7160 presets', function (): void {
    expect(LabelSheet::avery5163()->labelsPerPage())->toBe(10)
        ->and(LabelSheet::avery5163()->pageSize)->toBe('letter')
        ->and(LabelSheet::avery5163()->columns)->toBe(2)
        ->and(LabelSheet::avery5163()->rows)->toBe(5)
        ->and(LabelSheet::l7160()->labelsPerPage())->toBe(21)
        ->and(LabelSheet::l7160()->pageSize)->toBe('A4')
        ->and(LabelSheet::l7160()->columns)->toBe(3)
        ->and(LabelSheet::l7160()->rows)->toBe(7);
});

it('keeps a loose three-across sheet for the existing CSS grid', function (): void {
    $sheet = LabelSheet::threeAcross();

    expect($sheet->columns)->toBe(3)
        ->and($sheet->pageSize)->toBeNull()
        ->and($sheet->rows)->toBeNull()
        ->and($sheet->labelsPerPage())->toBeNull();
});

it('accepts a custom sheet', function (): void {
    $sheet = LabelSheet::make(
        columns: 2,
        rows: 4,
        labelWidth: '80mm',
        labelHeight: '40mm',
        pageSize: 'A4',
        marginTop: '10mm',
        marginRight: '10mm',
        marginBottom: '10mm',
        marginLeft: '10mm',
        columnGap: '2mm',
        rowGap: '2mm',
    );

    expect($sheet->labelsPerPage())->toBe(8)
        ->and($sheet->toCssVariables()['--fq-cols'])->toBe('2')
        ->and($sheet->toCssVariables()['--fq-label-width'])->toBe('80mm');
});

it('renders a QR as a data URI on the label', function (): void {
    $label = Label::make()->qr('SKU-1')->caption('Widget');

    expect($label->qrDataUri())->toStartWith('data:image/')
        ->and($label->getCaption())->toBe('Widget')
        ->and($label->barcodeDataUri())->toBeNull();
});

it('renders a barcode including 2D types', function (): void {
    $label = Label::make()
        ->barcode('09506000134352', BarcodeType::DataMatrix)
        ->heading('DM');

    expect($label->barcodeDataUri())->toStartWith('data:image/')
        ->and($label->getHeading())->toBe('DM')
        ->and($label->qrDataUri())->toBeNull();
});

it('encodes a Digital Link URI as the QR payload', function (): void {
    $elements = ['01' => '09506000134352', '10' => 'LOT-1'];
    $label = Label::make()->digitalLink($elements);

    expect($label->qrPayload())->toBe(DigitalLink::build($elements))
        ->and($label->qrDataUri())->toStartWith('data:image/');
});

it('chunks labels into pages and honours a first-sheet offset', function (): void {
    $sheet = LabelSheet::make(
        columns: 2,
        rows: 2,
        labelWidth: '40mm',
        labelHeight: '20mm',
        pageSize: 'A4',
    );
    $labels = [
        Label::make()->qr('A')->caption('A'),
        Label::make()->qr('B')->caption('B'),
        Label::make()->qr('C')->caption('C'),
        Label::make()->qr('D')->caption('D'),
    ];

    $html = LabelSheetRenderer::make($sheet)->offset(1)->toHtml($labels);

    expect($html)
        ->toContain('filament-qr-label-sheet')
        ->toContain('filament-qr-label')
        ->toContain('>A<')
        ->toContain('>B<')
        ->toContain('>C<')
        ->toContain('>D<')
        ->and(substr_count($html, 'filament-qr-label-sheet'))->toBe(2)
        // One leading skip, then three trailing cells so the last Avery page stays aligned.
        ->and(substr_count($html, 'filament-qr-label--empty'))->toBe(4);
});

it('can rasterize symbols as PNG for PDF engines that do not paint SVG', function (): void {
    $html = LabelSheetRenderer::make(LabelSheet::threeAcross())
        ->format(QrFormat::Png)
        ->toHtml([Label::make()->qr('SKU-1')]);

    expect($html)->toContain('data:image/png;base64,');
});
