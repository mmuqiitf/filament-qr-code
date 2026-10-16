<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Forms\Components\QrScanner;

it('cooldown defaults to 0 without checks and to scan.cooldown_ms with checks', function (): void {
    expect(QrScanner::make('sku')->getCooldownMs())->toBe(0)
        ->and(QrScanner::make('sku')->validateUsing(['max:5'])->getCooldownMs())->toBe(1500);

    config()->set('qr-code.scan.cooldown_ms', 900);

    expect(QrScanner::make('sku')->validateUsing(['max:5'])->getCooldownMs())->toBe(900);
});

it('lets an explicit cooldown win and clamps negatives to 0', function (): void {
    expect(QrScanner::make('sku')->cooldown(700)->getCooldownMs())->toBe(700)
        ->and(QrScanner::make('sku')->validateUsing(['max:5'])->cooldown(0)->getCooldownMs())->toBe(0)
        ->and(QrScanner::make('sku')->cooldown(-50)->getCooldownMs())->toBe(0)
        ->and(QrScanner::make('sku')->cooldown(fn (): int => 300)->getCooldownMs())->toBe(300);
});
