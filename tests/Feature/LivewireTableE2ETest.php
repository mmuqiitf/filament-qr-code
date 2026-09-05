<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Tables\Actions\DownloadQrAction;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

class TestProduct extends Model
{
    protected $table = 'test_products';

    protected $guarded = [];
}

class TestLivewireTableComponent extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(TestProduct::query())
            ->columns([
                TextColumn::make('name'),
                QrColumn::make('sku')
                    ->size(100)
                    ->downloadable(),
            ])
            ->actions([
                DownloadQrAction::make('downloadQr'),
            ]);
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>
            {{ $this->table }}
        </div>
        BLADE;
    }
}

beforeEach(function (): void {
    Schema::create('test_products', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('sku');
        $table->timestamps();
    });

    TestProduct::create(['name' => 'Scanner Unit A', 'sku' => 'SKU-UNIT-001']);
    TestProduct::create(['name' => 'Scanner Unit B', 'sku' => 'SKU-UNIT-002']);
});

it('renders QrColumn and DownloadQrAction in Livewire table end-to-end', function (): void {
    Livewire::test(TestLivewireTableComponent::class)
        ->assertSuccessful()
        ->assertSee('Scanner Unit A')
        ->assertSee('Scanner Unit B')
        ->assertSeeHtml('data:image/svg+xml;base64,');
});
