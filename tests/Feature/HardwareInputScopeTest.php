<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Enums\HardwareInputScope;
use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

it('defaults to focus scope', function () {
    $field = QrScanner::make('sku');

    expect($field->getHardwareInputScope())->toBe(HardwareInputScope::Focus)
        ->and($field->getScanPayload()['hardwareScope'])->toBe('focus');
});

it('sets page scope with hardwareInput', function () {
    $field = QrScanner::make('sku')->hardwareInput(scope: HardwareInputScope::Page);

    expect($field->getHardwareInputScope())->toBe(HardwareInputScope::Page)
        ->and($field->getScanPayload()['hardwareScope'])->toBe('page')
        ->and($field->isHardwareScannerEnabled())->toBeTrue();
});

it('accepts a closure for the scope', function () {
    $field = QrScanner::make('sku')->hardwareInput(scope: fn () => HardwareInputScope::Page);

    expect($field->getHardwareInputScope())->toBe(HardwareInputScope::Page);
});

it('reads hardware_scanner.default_scope from config', function () {
    config()->set('qr-code.hardware_scanner.default_scope', 'page');

    expect(QrScanner::make('sku')->getHardwareInputScope())->toBe(HardwareInputScope::Page);
});

it('falls back to focus for an invalid default_scope', function () {
    config()->set('qr-code.hardware_scanner.default_scope', 'everywhere');

    expect(QrScanner::make('sku')->getHardwareInputScope())->toBe(HardwareInputScope::Focus);
});

it('disables the hardware scanner with hardwareInput(enabled: false)', function () {
    expect(QrScanner::make('sku')->hardwareInput(enabled: false)->isHardwareScannerEnabled())->toBeFalse();
});

it('ships focus as the config default', function () {
    expect(config('qr-code.hardware_scanner.default_scope'))->toBe('focus');
});
