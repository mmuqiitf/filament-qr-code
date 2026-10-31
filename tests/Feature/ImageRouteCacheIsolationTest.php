<?php

declare(strict_types=1);

use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;
use Mmuqiitf\FilamentQrCode\Tables\Columns\QrColumn;

// Booting any Livewire component sets this static flag, and Livewire's middleware
// then marks the next response no-cache. With random test order, a component
// test that ran just before an image route test made its Cache-Control flaky.
it('leaves the back-button cache flag set, as a Livewire component test does', function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = true;

    expect(SupportDisablingBackButtonCache::$disableBackButtonCache)->toBeTrue();
});

it('serves cacheable image headers after a component test left the flag set', function () {
    config()->set('qr-code.image_route.ttl', 10);

    $this->get(QrColumn::make('qr')->data('ISOLATED')->getModalUrl())
        ->assertOk()
        ->assertHeader('Cache-Control', 'immutable, max-age=600, public');
})->depends('it leaves the back-button cache flag set, as a Livewire component test does');
