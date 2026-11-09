<?php

declare(strict_types=1);

use Livewire\Livewire;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeType;
use Mmuqiitf\FilamentQrCode\Enums\QrFormat;
use Mmuqiitf\FilamentQrCode\Forms\Components\BarcodeDisplay;
use Mmuqiitf\FilamentQrCode\Infolists\Components\BarcodeEntry;
use Mmuqiitf\FilamentQrCode\Tables\Columns\BarcodeColumn;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\BarcodeRenderComponent;

beforeEach(function (): void {
    BarcodeRenderComponent::$configure = null;
});

dataset('barcode components', [
    'display' => fn () => BarcodeDisplay::make('code'),
    'entry' => fn () => BarcodeEntry::make('code'),
    'column' => fn () => BarcodeColumn::make('code'),
]);

it('renders an accessible SVG by default', function ($component) {
    $render = $component->type(BarcodeType::Ean13)->data('400638133393')->getBarcodeRender();

    expect($render['alt'])->toBe('EAN-13 barcode: 4006381333931')
        ->and($render['error'])->toBeNull()
        ->and($render['extension'])->toBe('svg');

    if ($component instanceof BarcodeColumn) {
        expect($render['uri'])->toContain('filament-qr-code/barcode-image')
            ->and($render['uri'])->toContain('signature=');
        $this->get($render['uri'])->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

        return;
    }

    expect($render['uri'])->toStartWith('data:image/svg+xml;base64,');
})->with('barcode components');

it('applies every option', function ($component) {
    $component->type(BarcodeType::Code128)->data('ABC-1')->format(QrFormat::Png)
        ->barWidth(3)->height(50)->margin(4)->color('#1d4ed8')->backgroundColor('#fef3c7')->showText(false)->downloadable(false);

    $render = $component->getBarcodeRender();
    $png = str_starts_with($render['uri'], 'data:image/png;base64,')
        ? base64_decode(substr($render['uri'], strlen('data:image/png;base64,')))
        : $this->get($render['uri'])->assertOk()->assertHeader('Content-Type', 'image/png')->getContent();
    $image = imagecreatefromstring($png);

    expect($image)->toBeInstanceOf(GdImage::class)
        ->and($render['extension'])->toBe('png')
        ->and(imagesy($image))->toBe(50)
        ->and($component->isDownloadable())->toBeFalse()
        ->and($component->getType())->toBe(BarcodeType::Code128);
})->with('barcode components');

it('accepts the type as a string or closure', function ($component) {
    expect($component->type('upca')->getType())->toBe(BarcodeType::UpcA)
        ->and($component->type(fn () => BarcodeType::Itf14)->getType())->toBe(BarcodeType::Itf14);
})->with('barcode components');

it('returns an inline error instead of throwing for invalid values', function ($component) {
    $render = $component->type(BarcodeType::Ean13)->data('4006381333932')->getBarcodeRender();

    expect($render['uri'])->toBe('')
        ->and($render['error'])->toBe(__('filament-qr-code::ui.barcode_invalid', ['type' => 'EAN-13', 'reason' => 'wrong check digit, expected 1']));
})->with('barcode components');

it('reports no data for an empty value', function ($component) {
    $render = $component->data('')->getBarcodeRender();

    expect($render['uri'])->toBe('')->and($render['error'])->toBeNull();
})->with('barcode components');

it('defaults to Code 128 with text, and the column to a compact strip without text', function () {
    expect(BarcodeDisplay::make('a')->getType())->toBe(BarcodeType::Code128)
        ->and(BarcodeDisplay::make('a')->data('X')->getBarcodeService()->getRaw())->toContain('<text')
        ->and(BarcodeColumn::make('a')->data('X')->getBarcodeService()->getRaw())->not->toContain('<text');
});

it('renders the field and entry views with alt text and an inline error', function () {
    $html = Livewire::test(BarcodeRenderComponent::class)->html();

    expect($html)->toContain('alt="EAN-13 barcode: 4006381333931"')
        ->and($html)->toContain('alt="Code 39 barcode: HELLO-1"')
        ->and($html)->toContain('role="alert"')
        ->and($html)->toContain(e(__('filament-qr-code::ui.barcode_invalid', ['type' => 'EAN-13', 'reason' => 'wrong check digit, expected 1'])))
        ->and($html)->toContain(__('filament-qr-code::ui.barcode_no_data'))
        ->and($html)->toContain('download="barcode.svg"')
        ->and($html)->toContain('data-qr-part="barcode.image"');
});

it('translates the barcode messages', function () {
    app()->setLocale('id');

    expect(__('filament-qr-code::ui.barcode_no_data'))->not->toBe('filament-qr-code::ui.barcode_no_data')
        ->and(__('filament-qr-code::ui.barcode_invalid'))->not->toBe('filament-qr-code::ui.barcode_invalid');
});
