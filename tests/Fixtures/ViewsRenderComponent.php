<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Fixtures;

use Closure;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Support\ScanStep;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\Concerns\InteractsWithTestForm;

/** Renders every scan view once, for output snapshots. */
class ViewsRenderComponent extends Component implements HasForms
{
    use InteractsWithTestForm;

    public static ?Closure $configure = null;

    public function form(Schema $form): Schema
    {
        $components = [
            QrScanner::make('sku')->label('SKU')->allowUpload(),
            QrScanSequence::make()->statePath('receipt')->steps([
                ScanStep::make('po')->sticky(),
                ScanStep::make('serials')->repeat(2),
            ]),
            QrCollector::make('items'),
        ];

        if (self::$configure instanceof Closure) {
            $components = (self::$configure)($components);
        }

        return $form->statePath('data')->components($components);
    }
}
