<?php

arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('strict types in all files')
    ->expect('Mmuqiitf\FilamentQrCode')
    ->toUseStrictTypes()
    ->ignoring('Mmuqiitf\FilamentQrCode\Tests');

arch('enums are backed')
    ->expect('Mmuqiitf\FilamentQrCode\Enums')
    ->toBeEnums()
    ->toHaveMethod('label');

arch('service provider extends base')
    ->expect('Mmuqiitf\FilamentQrCode\QrCodeServiceProvider')
    ->toExtend('Spatie\LaravelPackageTools\PackageServiceProvider');

arch('plugin implements contract')
    ->expect('Mmuqiitf\FilamentQrCode\QrCodePlugin')
    ->toImplement('Filament\Contracts\Plugin');
