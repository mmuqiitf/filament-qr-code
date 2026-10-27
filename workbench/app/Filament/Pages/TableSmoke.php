<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

class TableSmoke extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'workbench::table-smoke';

    protected static ?string $slug = 'table-smoke';

    protected static ?string $title = 'Table smoke';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [1 => ['id' => 1, 'name' => 'Unit A', 'sku' => 'SKU-TABLE-1']])
            ->recordUrl(fn (array $record): string => url('/workbench/collect-smoke?record='.$record['id']))
            ->columns([
                TextColumn::make('name'),
                QrColumn::make('qr')
                    ->data(fn (array $record): string => $record['sku'])
                    ->downloadable(),
            ]);
    }
}
