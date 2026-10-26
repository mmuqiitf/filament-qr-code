<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderCache;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

beforeEach(function (): void {
    QrCodeService::cacheTtl(null);
    QrCodeService::persistentCache(true);
    QrCodeService::cacheStore(null);
    QrCodeService::flushRenderCache();
    Cache::flush();
});

afterEach(function (): void {
    QrCodeService::cacheTtl(null);
    QrCodeService::flushRenderCache();
});

function renderCacheKey(string $key): string
{
    return 'filament-qr-code:render:'.$key;
}

it('turns the persistent cache off when generator.cache_ttl is false', function (): void {
    config()->set('qr-code.generator.cache_ttl', false);
    Cache::put(renderCacheKey('k'), 'stale');

    $value = QrRenderCache::remember('k', fn (): string => 'fresh');

    expect($value)->toBe('fresh')
        ->and(Cache::get(renderCacheKey('k')))->toBe('stale')
        ->and(QrCodeService::effectiveCacheTtl())->toBeFalse();

    Cache::forget(renderCacheKey('k'));
    QrRenderCache::remember('k2', fn (): string => 'other');
    expect(Cache::has(renderCacheKey('k2')))->toBeFalse();
});

it('keeps the in-process cache when generator.cache_ttl is false', function (): void {
    config()->set('qr-code.generator.cache_ttl', false);
    $renders = 0;

    QrRenderCache::remember('k', function () use (&$renders): string {
        $renders++;

        return 'v';
    });
    QrRenderCache::remember('k', function () use (&$renders): string {
        $renders++;

        return 'v';
    });

    expect($renders)->toBe(1);
});

it('caches forever when generator.cache_ttl is null', function (): void {
    config()->set('qr-code.generator.cache_ttl', null);

    QrRenderCache::remember('k', fn (): string => 'v');

    expect(Cache::get(renderCacheKey('k')))->toBe('v')
        ->and(QrCodeService::effectiveCacheTtl())->toBeNull();
});

it('caches for the configured seconds when generator.cache_ttl is an int', function (): void {
    config()->set('qr-code.generator.cache_ttl', 60);

    QrRenderCache::remember('k', fn (): string => 'v');

    expect(Cache::get(renderCacheKey('k')))->toBe('v')
        ->and(QrCodeService::effectiveCacheTtl())->toBe(60);

    $this->travel(61)->seconds();
    expect(Cache::has(renderCacheKey('k')))->toBeFalse();
});

it('lets cacheTtl(false) turn the persistent cache off at runtime', function (): void {
    config()->set('qr-code.generator.cache_ttl', 60);
    QrCodeService::cacheTtl(false);

    QrRenderCache::remember('k', fn (): string => 'v');

    expect(Cache::has(renderCacheKey('k')))->toBeFalse()
        ->and(QrCodeService::effectiveCacheTtl())->toBeFalse();
});

it('falls back to the config when cacheTtl(null) clears the override', function (): void {
    config()->set('qr-code.generator.cache_ttl', 60);
    QrCodeService::cacheTtl(10);
    expect(QrCodeService::effectiveCacheTtl())->toBe(10);

    QrCodeService::cacheTtl(null);
    expect(QrCodeService::effectiveCacheTtl())->toBe(60);
});
