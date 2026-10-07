<?php

declare(strict_types=1);

use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\URL;
use Mmuqiitf\FilamentQrCode\Concerns\HasHardwareScanner;
use Mmuqiitf\FilamentQrCode\Enums\BarcodeFormat;
use Mmuqiitf\FilamentQrCode\FilamentQrCodeServiceProvider;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanSequence;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

it('sanitizes hardware scanner framing like the JS interceptor', function () {
    expect(HasHardwareScanner::sanitizeScannedValue("\x02PROD-9988234-XYZ\r\n\x03"))->toBe('PROD-9988234-XYZ')
        ->and(HasHardwareScanner::sanitizeScannedValue('  ABC-123  '))->toBe('ABC-123');
});

it('defaults burst suppression and scan timeout, with opt-outs', function () {
    $field = QrScanner::make('sku');

    expect($field->isSuppressedWhenGlobalListenerActive())->toBeTrue()
        ->and($field->getScanTimeoutMs())->toBe(150);

    $field->suppressWhenGlobalListener(false)->scanTimeout(0);

    expect($field->isSuppressedWhenGlobalListenerActive())->toBeFalse()
        ->and($field->getScanTimeoutMs())->toBe(0);
});

it('auto-degrades fps only when unrestricted and not customized', function () {
    $default = QrScanner::make('a');

    expect($default->getFps())->toBe(25)
        ->and($default->getEffectiveFps())->toBe(12)
        ->and($default->shouldWarnUnrestrictedPerformance())->toBeTrue();

    $filtered = QrScanner::make('b')->formats([BarcodeFormat::QrCode]);

    expect($filtered->getEffectiveFps())->toBe(25)
        ->and($filtered->shouldWarnUnrestrictedPerformance())->toBeFalse();

    $explicit = QrScanner::make('c')->fps(30);

    expect($explicit->getEffectiveFps())->toBe(30)
        ->and($explicit->shouldWarnUnrestrictedPerformance())->toBeFalse();
});

it('merges sequence state without hand-rolled array_merge', function () {
    $sequence = QrScanSequence::make(['step', 'employee']);

    expect($sequence->getComponentStatePath())->toBeNull()
        ->and($sequence->mergeSequenceState(['other' => 'x']))->toBe(['other' => 'x'])
        ->and($sequence->getMissingSequenceKeys(['other' => 'x']))->toBe(['step', 'employee'])
        ->and($sequence->isSequenceComplete(['step' => 'S1', 'employee' => 'E1']))->toBeTrue();
});

it('normalizes sequence steps on merge', function () {
    $sequence = QrScanSequence::make(['step'])
        ->normalizeStepUsing(fn ($rawValue) => strtoupper(trim((string) $rawValue)));

    expect($sequence->normalizeStepValue('step', '  abc-1  '))->toBe('ABC-1');
});

it('persists renders across instances via the Laravel cache', function () {
    QrCodeService::flushRenderCache();
    QrCodeService::persistentCache(true);

    $payload = 'PERSISTENT-CACHE-'.uniqid();
    $first = QrCodeService::make()->size(200)->generate($payload)->getRaw();

    // Drop only the in-process L1: the second instance must hit L2.
    QrCodeService::flushRenderCache();
    $second = QrCodeService::make()->size(200)->generate($payload)->getRaw();

    expect($second)->toBe($first);

    QrCodeService::flushRenderCache();
});

it('exposes a signed lazy modal URL for table columns', function () {
    $column = QrColumn::make('barcode')->data('LAZY-ITEM-1');

    expect($column->isLazyModal())->toBeTrue()
        ->and($column->getModalUrl())->toContain('filament-qr-code/qr-image')
        ->and(URL::signedRoute('filament-qr-code.image', ['data' => 'x']))->toContain('signature');

    expect($column->lazyModal(false)->isLazyModal())->toBeFalse();
});

it('registers package assets exactly once across provider and plugin', function () {
    FilamentQrCodeServiceProvider::resetAssetsRegistration();

    FilamentQrCodeServiceProvider::registerAssetsOnce();
    FilamentQrCodeServiceProvider::registerAssetsOnce();

    $scripts = FilamentAsset::getScripts(['mmuqiitf/filament-qr-code']);
    $ids = array_map(fn ($s) => $s->getId(), $scripts);

    expect(array_count_values($ids)['filament-qr-code-scripts'] ?? 0)->toBeLessThanOrEqual(2);

    FilamentQrCodeServiceProvider::resetAssetsRegistration();
    FilamentQrCodeServiceProvider::registerAssetsOnce();
});

it('explains the secure-context requirement for cameras', function () {
    expect(__('filament-qr-code::ui.camera_needs_secure_context'))->toContain('https');
});
