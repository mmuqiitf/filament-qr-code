<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mmuqiitf\FilamentQrCode\Tables\Actions\PrintLabelsAction;
use Workbench\App\Models\LabelSmokeProduct;

class LabelsSmoke extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'workbench::labels-smoke';

    protected static ?string $slug = 'labels-smoke';

    protected static ?string $title = 'Labels smoke';

    public function mount(): void
    {
        self::ensureRecords();
    }

    public function table(Table $table): Table
    {
        self::ensureRecords();

        return $table
            ->query(LabelSmokeProduct::query())
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('sku'),
            ])
            ->actions([
                PrintLabelsAction::make()
                    ->button()
                    ->qrData(fn (LabelSmokeProduct $record): string => $record->sku)
                    ->caption(fn (LabelSmokeProduct $record): string => $record->name),
            ]);
    }

    protected static function ensureRecords(): void
    {
        if (! Schema::hasTable('label_smoke_products')) {
            Schema::create('label_smoke_products', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('sku');
            });

            LabelSmokeProduct::query()->create([
                'name' => 'Unit A',
                'sku' => 'SKU-LABEL-1',
            ]);
        }
    }
}
