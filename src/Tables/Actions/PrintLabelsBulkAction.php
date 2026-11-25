<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Component;
use Mmuqiitf\FilamentQrCode\Tables\Actions\Concerns\HasLabelSheet;

class PrintLabelsBulkAction extends BulkAction
{
    use HasLabelSheet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('print_labels_bulk');
        $this->label(__('filament-qr-code::ui.print_labels_bulk'));
        $this->icon('heroicon-o-printer');
        $this->color('gray');
        $this->deselectRecordsAfterCompletion();

        $this->action(function (Collection $records, Component $livewire): void {
            $url = $this->storePrint($records);

            if ($url === null) {
                Notification::make()->title(__('filament-qr-code::ui.no_labels'))->warning()->send();

                return;
            }

            $livewire->js('window.open('.json_encode($url).', "_blank")');
        });
    }
}
