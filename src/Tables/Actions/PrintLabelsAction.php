<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tables\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Livewire\Component;
use Mmuqiitf\FilamentQrCode\Tables\Actions\Concerns\HasLabelSheet;

class PrintLabelsAction extends Action
{
    use HasLabelSheet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->name('print_labels');
        $this->label(__('filament-qr-code::ui.print_labels'));
        $this->icon('heroicon-o-printer');
        $this->color('gray');

        $this->action(function (?Model $record, Component $livewire): void {
            $url = $this->storePrint(new Collection($record !== null ? [$record] : []));

            if ($url === null) {
                Notification::make()->title(__('filament-qr-code::ui.no_labels'))->warning()->send();

                return;
            }

            $livewire->js('window.open('.json_encode($url).', "_blank")');
        });
    }
}
