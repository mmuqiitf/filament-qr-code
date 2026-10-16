<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Mmuqiitf\FilamentQrCode\Enums\ScanInput;
use Mmuqiitf\FilamentQrCode\Support\ScanResult;

it('builds an accepted result for the browser', function (): void {
    $record = new class extends Model
    {
        protected $guarded = [];
    };
    $record->forceFill(['id' => 42]);

    $result = ScanResult::accept('SKU-1', ScanInput::Hardware, $record);

    expect($result->passes)->toBeTrue()
        ->and($result->record)->toBe($record)
        ->and($result->toClient())->toBe([
            'passes' => true,
            'code' => 'SKU-1',
            'source' => 'hardware',
            'message' => null,
            'record_key' => 42,
        ]);
});

it('builds a rejected result with its message', function (): void {
    expect(ScanResult::reject('BAD', ScanInput::Camera, 'Nope')->toClient())->toBe([
        'passes' => false,
        'code' => 'BAD',
        'source' => 'camera',
        'message' => 'Nope',
        'record_key' => null,
    ]);
});

it('allows a null source for submit-time checks', function (): void {
    expect(ScanResult::accept('SKU-1', null)->toClient()['source'])->toBeNull();
});
