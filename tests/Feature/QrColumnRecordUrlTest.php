<?php

declare(strict_types=1);

namespace Mmuqiitf\FilamentQrCode\Tests\Feature;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

class RecordUrlTableComponent extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public bool $previewable = true;

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => [1 => ['id' => 1, 'name' => 'Unit A', 'sku' => 'SKU-1']])
            ->recordUrl(fn (array $record): string => '/records/'.$record['id'])
            ->columns([
                TextColumn::make('name'),
                QrColumn::make('qr')
                    ->data(fn (array $record): string => $record['sku'])
                    ->previewable($this->previewable),
            ]);
    }

    public function render(): string
    {
        return '<div>{{ $this->table }}</div>';
    }
}

function qrCellXPath(string $html): DOMXPath
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

    return new DOMXPath($dom);
}

function insideAnchor(DOMElement $element): bool
{
    for ($node = $element->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
        if ($node->tagName === 'a') {
            return true;
        }
    }

    return false;
}

it('keeps a previewable QrColumn out of the row record link', function (): void {
    $html = Livewire::test(RecordUrlTableComponent::class)->html();
    $xpath = qrCellXPath($html);

    $thumb = $xpath->query('//img[@alt="QR Code"]')->item(0);
    expect($thumb)->toBeInstanceOf(DOMElement::class)
        ->and(insideAnchor($thumb))->toBeFalse()
        ->and($xpath->query('//a//a')->length)->toBe(0)
        ->and($xpath->query('//a[contains(@href, "/records/1")]')->length)->toBeGreaterThan(0);
});

it('still links a non-previewable QrColumn to the record', function (): void {
    $html = Livewire::test(RecordUrlTableComponent::class, ['previewable' => false])->html();

    $thumb = qrCellXPath($html)->query('//img[@alt="QR Code"]')->item(0);
    expect($thumb)->toBeInstanceOf(DOMElement::class)
        ->and(insideAnchor($thumb))->toBeTrue();
});
