<?php

use Mmuqiitf\FilamentQrCode\Enums\CameraFacing;
use Mmuqiitf\FilamentQrCode\Enums\ScanMode;

it('camera facing has correct values', function () {
    expect(CameraFacing::Front->value)->toBe('user');
    expect(CameraFacing::Back->value)->toBe('environment');
    expect(CameraFacing::Auto->value)->toBe('auto');
});

it('camera facing has labels', function () {
    expect(CameraFacing::Front->label())->toBeString();
    expect(CameraFacing::Back->label())->toBeString();
    expect(CameraFacing::Auto->label())->toBeString();
});

it('scan mode has correct values', function () {
    expect(ScanMode::Single->value)->toBe('single');
    expect(ScanMode::Continuous->value)->toBe('continuous');
    expect(ScanMode::Sequence->value)->toBe('sequence');
});

it('scan mode has labels', function () {
    expect(ScanMode::Single->label())->toBeString();
    expect(ScanMode::Continuous->label())->toBeString();
    expect(ScanMode::Sequence->label())->toBeString();
});
