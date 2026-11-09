<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;
use Mmuqiitf\FilamentQrCode\Infolists\Components\BarcodeEntry;

class BarcodeSmoke extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'workbench::barcode-smoke';

    protected static ?string $slug = 'barcode-smoke';

    protected static ?string $title = 'Barcode smoke';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'code128' => 'SKU-128-A',
            'ean13' => '4006381333931',
            'ean8' => '96385074',
            'upca' => '036000291452',
            'code39' => 'HELLO-1',
            'itf14' => '00012345678905',
            'gs1128' => '(01)09521101530001(17)251231(10)LOT-1',
            'invalid' => '4006381333932',
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form->statePath('data')->components([
            BarcodeDisplay::make('code128')->label('Code 128')->type(BarcodeType::Code128),
            BarcodeDisplay::make('ean13')->label('EAN-13')->type(BarcodeType::Ean13),
            BarcodeDisplay::make('ean8')->label('EAN-8')->type(BarcodeType::Ean8)->format(QrFormat::Png),
            BarcodeDisplay::make('upca')->label('UPC-A')->type(BarcodeType::UpcA)->showText(false),
            BarcodeDisplay::make('code39')->label('Code 39')->type(BarcodeType::Code39),
            BarcodeDisplay::make('itf14')->label('ITF-14')->type(BarcodeType::Itf14),
            BarcodeDisplay::make('gs1128')->label('GS1-128')->type(BarcodeType::Gs1128),
            BarcodeDisplay::make('invalid')->label('Invalid EAN-13')->type(BarcodeType::Ean13),
            BarcodeEntry::make('entry')->label('Entry')->type(BarcodeType::Code128)->data('ENTRY-1'),
        ]);
    }
}
