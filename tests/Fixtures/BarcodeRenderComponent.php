<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Closure;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;
use Mmuqiitf\FilamentQrCode\Infolists\Components\BarcodeEntry;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\Concerns\InteractsWithTestForm;

/** Renders the barcode form field and infolist entry for view tests. */
class BarcodeRenderComponent extends Component implements HasForms
{
    use InteractsWithTestForm;

    public static ?Closure $configure = null;

    public function mount(): void
    {
        $this->form->fill(['ean' => '4006381333931', 'bad' => '4006381333932', 'empty' => '']);
    }

    public function form(Schema $form): Schema
    {
        $components = [
            BarcodeDisplay::make('ean')->type(BarcodeType::Ean13),
            BarcodeDisplay::make('bad')->type(BarcodeType::Ean13),
            BarcodeDisplay::make('empty')->type(BarcodeType::Code128),
            BarcodeEntry::make('entry')->type(BarcodeType::Code39)->data('HELLO-1'),
        ];

        if (self::$configure instanceof Closure) {
            $components = (self::$configure)($components);
        }

        return $form->statePath('data')->components($components);
    }
}
