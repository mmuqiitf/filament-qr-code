<?php

declare(strict_types=1);

use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\Concerns\InteractsWithTestForm;

class CollectorQtyCsvMaxLivewireComponent extends Component implements HasForms
{
    use InteractsWithTestForm;

    public bool $useQuantity = false;

    public ?int $maxItems = null;

    public bool $allowDuplicates = false;

    public bool $useGs1 = false;

    /** @var array<int, mixed> */
    public array $itemRules = [];

    public function form(Schema $form): Schema
    {
        $collector = QrCollector::make('codes')
            ->allowDuplicates($this->allowDuplicates)
            ->quantity($this->useQuantity)
            ->maxItems($this->maxItems)
            ->csvImport()
            ->csvExport(fileName: 'shelf-audit.csv')
            ->normalizeWith(uppercase: true, stripPrefix: [']C1']);

        if ($this->useGs1) {
            $collector->gs1(require: ['01']);
        }

        if ($this->itemRules !== []) {
            $collector->itemRules($this->itemRules);
        }

        return $form
            ->schema([$collector])
            ->statePath('data');
    }
}

it('keeps quantity, maxItems, and csv opt-in with unchanged default payload', function (): void {
    $collector = QrCollector::make('items');
    $payload = $collector->getScanPayload();

    expect($collector->hasQuantity())->toBeFalse()
        ->and($collector->getMaxItems())->toBeNull()
        ->and($collector->isCsvImportEnabled())->toBeFalse()
        ->and($collector->isCsvExportEnabled())->toBeFalse()
        ->and($payload)->not->toHaveKey('quantity')
        ->and($payload)->not->toHaveKey('maxItems')
        ->and($payload)->not->toHaveKey('maxItemsMessage')
        ->and($payload)->not->toHaveKey('csvImport')
        ->and($payload)->not->toHaveKey('csvExport')
        ->and($payload)->not->toHaveKey('csvFileName');
});

it('exposes quantity, maxItems, and csv options in the scan payload when enabled', function (): void {
    $collector = QrCollector::make('items')
        ->quantity()
        ->maxItems(5)
        ->csvImport()
        ->csvExport(fileName: 'audit.csv');

    $payload = $collector->getScanPayload();

    expect($collector->hasQuantity())->toBeTrue()
        ->and($collector->getMaxItems())->toBe(5)
        ->and($collector->isCsvImportEnabled())->toBeTrue()
        ->and($collector->isCsvExportEnabled())->toBeTrue()
        ->and($collector->getCsvFileName())->toBe('audit.csv')
        ->and($payload['quantity'])->toBeTrue()
        ->and($payload['maxItems'])->toBe(5)
        ->and($payload['maxItemsMessage'])->toContain(':max')
        ->and($payload['csvImport'])->toBeTrue()
        ->and($payload['csvExport'])->toBeTrue()
        ->and($payload['csvFileName'])->toBe('audit.csv');
});

it('rejects non-positive maxItems', function (): void {
    expect(fn () => QrCollector::make('items')->maxItems(0))
        ->toThrow(InvalidArgumentException::class);
});

it('enforces maxItems on submit in both standard and quantity modes', function (): void {
    $test = Livewire::test(CollectorQtyCsvMaxLivewireComponent::class)
        ->set('maxItems', 2);

    $test->set('data.codes', ['PRD-1', 'PRD-2', 'PRD-3'])
        ->call('submit')
        ->assertHasErrors(['data.codes']);

    $test->set('data.codes', ['PRD-1', 'PRD-2'])
        ->call('submit')
        ->assertHasNoErrors();

    $qtyTest = Livewire::test(CollectorQtyCsvMaxLivewireComponent::class)
        ->set('useQuantity', true)
        ->set('maxItems', 2);

    $qtyTest->set('data.codes', [
        ['code' => 'PRD-1', 'qty' => 5],
        ['code' => 'PRD-2', 'qty' => 3],
        ['code' => 'PRD-3', 'qty' => 1],
    ])
        ->call('submit')
        ->assertHasErrors(['data.codes']);

    $qtyTest->set('data.codes', [
        ['code' => 'PRD-1', 'qty' => 5],
        ['code' => 'PRD-2', 'qty' => 3],
    ])
        ->call('submit')
        ->assertHasNoErrors();
});

it('validates quantity entries on submit when quantity mode is enabled', function (): void {
    $test = Livewire::test(CollectorQtyCsvMaxLivewireComponent::class)
        ->set('useQuantity', true);

    $test->set('data.codes', [
        ['code' => 'PRD-1', 'qty' => 0],
    ])
        ->call('submit')
        ->assertHasErrors(['data.codes']);

    $test->set('data.codes', [
        ['code' => 'PRD-1', 'qty' => 2],
    ])
        ->call('submit')
        ->assertHasNoErrors();
});

it('exports collected items to CSV with or without qty column', function (): void {
    $plain = QrCollector::make('items')->csvExport();
    expect($plain->toCsv(['PRD-1', 'PRD, "2"']))
        ->toBe("code\nPRD-1\n\"PRD, \"\"2\"\"\"\n");

    $qty = QrCollector::make('items')->quantity()->csvExport();
    expect($qty->toCsv([
        ['code' => 'PRD-1', 'qty' => 3],
        ['code' => 'PRD,2', 'qty' => 1],
    ]))->toBe("code,qty\nPRD-1,3\n\"PRD,2\",1\n");
});

it('imports CSV rows with normalizeWith, itemRules, duplicate handling, and maxItems', function (): void {
    $test = Livewire::test(CollectorQtyCsvMaxLivewireComponent::class)
        ->set('maxItems', 3)
        ->set('itemRules', ['regex:/^PRD-\d+$/']);
    $key = $test->instance()->keyOf('codes');

    $csv = "\xEF\xBB\xBFcode\r\n ]C1prd-1 \r\nBAD-99\r\nPRD-1\r\nPRD-2\r\nPRD-3\r\nPRD-4\r\n";

    $test->call('callSchemaComponentMethod', $key, 'importCollectorCsv', [
        'csv' => $csv,
        'existing' => [],
    ])->assertReturned(function (array $result): bool {
        return $result['items'] === ['PRD-1', 'PRD-2', 'PRD-3']
            && $result['added'] === 3
            && count($result['rejected']) === 3
            && $result['rejected'][0]['row'] === 3
            && $result['rejected'][0]['code'] === 'BAD-99'
            && $result['rejected'][1]['row'] === 4
            && $result['rejected'][1]['code'] === 'PRD-1'
            && $result['rejected'][2]['row'] === 7
            && $result['rejected'][2]['code'] === 'PRD-4';
    });
});

it('imports CSV rows in quantity mode, merges quantities, respects GS1 and rejects invalid qty', function (): void {
    $test = Livewire::test(CollectorQtyCsvMaxLivewireComponent::class)
        ->set('useQuantity', true)
        ->set('useGs1', true)
        ->set('maxItems', 2);
    $key = $test->instance()->keyOf('codes');

    $validA = '(01)09506000134352(10)LOT1';
    $rawA = '010950600013435210LOT1';
    $validB = '(01)09506000134352(10)LOT2';
    $validC = '(01)09506000134352(10)LOT3';
    $badCheckDigit = '010950600013435310BAD';

    $csv = implode("\n", [
        'code,qty',
        "\"{$rawA}\",2",
        "\"{$validA}\",3",
        "\"{$badCheckDigit}\",1",
        "\"{$validB}\",0",
        "\"{$validB}\",4",
        "\"{$validC}\",1",
    ]);

    $test->call('callSchemaComponentMethod', $key, 'importCollectorCsv', [
        'csv' => $csv,
        'existing' => [],
    ])->assertReturned(function (array $result) use ($validA, $validB, $validC): bool {
        return $result['items'] === [
            ['code' => $validA, 'qty' => 5],
            ['code' => $validB, 'qty' => 4],
        ]
            && $result['added'] === 3
            && count($result['rejected']) === 3
            && $result['rejected'][0]['row'] === 4
            && $result['rejected'][1]['row'] === 5
            && $result['rejected'][1]['code'] === $validB
            && $result['rejected'][2]['row'] === 7
            && $result['rejected'][2]['code'] === $validC;
    });
});

it('renders quantity controls, max items badge, and csv buttons when enabled', function (): void {
    $html = Livewire::test(CollectorQtyCsvMaxLivewireComponent::class)
        ->set('useQuantity', true)
        ->set('maxItems', 5)
        ->html();

    expect($html)
        ->toContain('data-qr-part="collector.qty"')
        ->toContain('data-qr-part="collector.import-csv"')
        ->toContain('data-qr-part="collector.export-csv"')
        ->toContain('data-qr-part="collector.import-report"')
        ->toContain('/ 5');
});
