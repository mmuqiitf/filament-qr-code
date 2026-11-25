<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Gs1\DigitalLink;
use Mmuqiitf\FilamentQrCode\Printing\Label;
use Mmuqiitf\FilamentQrCode\Printing\LabelSheet;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsAction;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsBulkAction;

it('configures the row and bulk print actions', function (): void {
    $row = PrintLabelsAction::make()->sheet(LabelSheet::l7160());
    $bulk = PrintLabelsBulkAction::make();

    expect($row->getName())->toBe('print_labels')
        ->and($row->getSheet()->labelsPerPage())->toBe(21)
        ->and($bulk->getName())->toBe('print_labels_bulk')
        ->and($bulk->getSheet()->labelsPerPage())->toBe(30);
});

it('builds a label from qr, barcode and caption callbacks', function (): void {
    $action = PrintLabelsAction::make()
        ->qrData(fn ($record) => $record['sku'])
        ->barcodeData(fn ($record) => $record['gtin'], BarcodeType::DataMatrix)
        ->caption(fn ($record) => $record['name']);

    $labels = $action->labelsForRecords(new Collection([
        ['sku' => 'SKU-1', 'gtin' => '09506000134352', 'name' => 'Widget'],
        ['sku' => '', 'gtin' => '', 'name' => 'Skip me'],
    ]));

    expect($labels)->toHaveCount(1)
        ->and($labels[0]->qrPayload())->toBe('SKU-1')
        ->and($labels[0]->getCaption())->toBe('Widget')
        ->and($labels[0]->barcodeDataUri())->toStartWith('data:image/');
});

it('can encode a Digital Link QR and a GS1 DataMatrix on the same label', function (): void {
    $elements = ['01' => '09506000134352', '10' => 'LOT-1'];

    $action = PrintLabelsAction::make()
        ->digitalLink(fn ($record) => $elements)
        ->barcodeData(fn ($record) => '(01)09506000134352(10)LOT-1', BarcodeType::Gs1DataMatrix)
        ->heading(fn ($record) => $record['sku']);

    $labels = $action->labelsForRecords(new Collection([['sku' => 'SKU-1']]));

    expect($labels)->toHaveCount(1)
        ->and($labels[0]->qrPayload())->toBe(DigitalLink::build($elements))
        ->and($labels[0]->getHeading())->toBe('SKU-1')
        ->and($labels[0]->barcodeDataUri())->toStartWith('data:image/');
});

it('lets labelUsing replace the default builders', function (): void {
    $action = PrintLabelsAction::make()
        ->qrData('ignored')
        ->labelUsing(fn ($record) => Label::make()->qr('CUSTOM-'.$record['sku'])->caption($record['sku']));

    $labels = $action->labelsForRecords(new Collection([['sku' => 'A']]));

    expect($labels[0]->qrPayload())->toBe('CUSTOM-A')
        ->and($labels[0]->getCaption())->toBe('A');
});

it('repeats each label when copies is greater than one', function (): void {
    $action = PrintLabelsBulkAction::make()
        ->qrData(fn ($record) => $record['sku'])
        ->copies(2);

    $labels = $action->labelsForRecords(new Collection([
        ['sku' => 'A'],
        ['sku' => 'B'],
    ]));

    expect(array_map(fn (Label $label) => $label->qrPayload(), $labels))->toBe(['A', 'A', 'B', 'B']);
});

it('stores a print document and serves it on the print route', function (): void {
    $action = PrintLabelsAction::make()
        ->sheet(LabelSheet::threeAcross())
        ->qrData(fn ($record) => $record['sku'])
        ->caption(fn ($record) => $record['sku']);

    $url = $action->storePrint(new Collection([['sku' => 'PRINT-1']]));

    expect($url)->toContain('filament-qr-code/labels/')
        ->and(Cache::has('filament-qr-code.labels.'.basename(parse_url($url, PHP_URL_PATH))))->toBeTrue();

    $this->get($url)
        ->assertOk()
        ->assertSee('PRINT-1', false)
        ->assertSee('filament-qr-label-sheet', false)
        ->assertSee('window.print()', false);
});

it('returns 404 for an unknown print token', function (): void {
    $this->get(url('filament-qr-code/labels/missing-token'))->assertNotFound();
});

it('returns null instead of a URL when every record is empty', function (): void {
    $action = PrintLabelsBulkAction::make()
        ->qrData(fn ($record) => $record['sku']);

    expect($action->storePrint(new Collection([
        ['sku' => ''],
        ['sku' => null],
    ])))->toBeNull();
});
