<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Support\ScanCheckPipeline;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;
use Mmuqiitf\FilamentQrCode\Tests\Fixtures\ScanCheckProduct;

beforeEach(fn () => ScanCheckProduct::createTableWithProducts());

function runPipeline(ScanCheckPipeline $pipeline, string $code): ScanResult
{
    return $pipeline->run(
        $code,
        ScanInput::Hardware,
        fn (Closure $closure, array $named = [], array $typed = []): mixed => app()->call($closure, $named),
        'SKU',
    );
}

it('pipeline runs rules, lookup and closures in order and stops at the first failure', function () {
    $calls = new ArrayObject;
    $pipeline = new ScanCheckPipeline(
        rules: ['min:4'],
        closures: [function (string $code, ?ScanCheckProduct $record) use ($calls): ?string {
            $calls[] = $code;

            return $record?->stock > 0 ? null : 'Out of stock';
        }],
        lookupModel: ScanCheckProduct::class,
        lookupColumn: 'sku',
    );

    expect(runPipeline($pipeline, 'SKU')->message)->toContain('at least 4')
        ->and(runPipeline($pipeline, 'SKU-404')->message)->toBe(__('filament-qr-code::ui.record_not_found', ['code' => 'SKU-404']))
        ->and($calls->getArrayCopy())->toBe([])
        ->and(runPipeline($pipeline, 'SKU-EMPTY')->message)->toBe('Out of stock');

    $accepted = runPipeline($pipeline, 'SKU-1');

    expect($accepted->passes)->toBeTrue()
        ->and($accepted->record?->getAttribute('sku'))->toBe('SKU-1')
        ->and($calls->getArrayCopy())->toBe(['SKU-EMPTY', 'SKU-1']);
});

it('rejects an empty code and turns exceptions into scan_failed', function () {
    $pipeline = new ScanCheckPipeline(closures: [fn () => throw new RuntimeException('boom')]);

    expect(runPipeline($pipeline, '')->message)->toBe(__('filament-qr-code::ui.scan_empty'))
        ->and(runPipeline($pipeline, 'X')->message)->toBe(__('filament-qr-code::ui.scan_failed'));
});

it('passes when there are no checks', function () {
    expect(runPipeline(new ScanCheckPipeline, 'ANY')->passes)->toBeTrue();
});
