<?php

declare(strict_types=1);

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrCollector;

it('configures QrCollector properly', function () {
    $collector = QrCollector::make('items')
        ->allowDuplicates(false)
        ->delayBetweenScans(1500)
        ->fps(25)
        ->qrbox(200);

    expect($collector->getName())->toBe('items')
        ->and($collector->isDuplicatesAllowed())->toBeFalse()
        ->and($collector->getDelayBetweenScansMs())->toBe(1500)
        ->and($collector->getFps())->toBe(25)
        ->and($collector->getQrbox())->toBe(200);
});

it('filters symbologies and prefers the rear camera by default', function () {
    $collector = QrCollector::make('items')
        ->formats([BarcodeFormat::QrCode, BarcodeFormat::Code128]);

    expect($collector->getSupportedFormats())->toBe(['QR_CODE', 'CODE_128'])
        ->and($collector->isPreferRearCamera())->toBeTrue();
});

it('does not assume a handleCollectorScan hook without a Livewire host (#35)', function () {
    expect(QrCollector::make('items')->getScanPayload()['wireScanHandler'])->toBeFalse();
});

it('tells the browser whether the hosting Livewire component defines handleCollectorScan', function () {
    $withHook = new class extends Component implements HasForms
    {
        use InteractsWithForms;

        public function handleCollectorScan(string $code): void {}
    };

    $withoutHook = new class extends Component implements HasForms
    {
        use InteractsWithForms;
    };

    $payloadFor = fn ($livewire): bool => QrCollector::make('items')
        ->container(Schema::make($livewire))
        ->getScanPayload()['wireScanHandler'];

    expect($payloadFor($withHook))->toBeTrue()
        ->and($payloadFor($withoutHook))->toBeFalse();
});

it('does not fail when its schema has no Livewire component', function () {
    expect(QrCollector::make('items')->container(Schema::make())->getScanPayload()['wireScanHandler'])->toBeFalse();
});
