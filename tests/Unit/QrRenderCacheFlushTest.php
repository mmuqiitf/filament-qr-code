<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Mmuqiitf\FilamentQrCode\Rendering\QrRenderCache;
use Mmuqiitf\FilamentQrCode\Services\QrCodeService;

beforeEach(function (): void {
    QrCodeService::cacheTtl(null);
    QrCodeService::persistentCache(true);
    QrCodeService::cacheStore(null);
    QrCodeService::flushRenderCache();
});

afterEach(function (): void {
    QrCodeService::cacheStore(null);
    QrCodeService::cacheTtl(null);
    QrCodeService::flushRenderCache();
});

it('retires a render generation on the file and database stores', function (string $driver) {
    [$store, $cleanup] = isolatedRenderStore($driver);
    $renders = 0;
    $render = function () use (&$renders): string {
        $renders++;

        return 'render-'.$renders;
    };

    try {
        QrRenderCache::remember('k', $render);
        QrRenderCache::remember('k', $render);
        Cache::store($store)->forever('app-setting', 'keep');

        expect($renders)->toBe(1)
            ->and(Cache::store($store)->get('filament-qr-code:render:v1:k'))->toBe('render-1');

        QrCodeService::flushPersistentCache();
        QrRenderCache::remember('k', $render);

        expect($renders)->toBe(2)
            ->and(Cache::store($store)->get('filament-qr-code:render-version'))->toBe(2)
            ->and(Cache::store($store)->get('filament-qr-code:render:v1:k'))->toBe('render-1')
            ->and(Cache::store($store)->get('filament-qr-code:render:v2:k'))->toBe('render-2')
            ->and(Cache::store($store)->get('filament-qr-code:render-index'))->toBeNull()
            ->and(Cache::store($store)->get('app-setting'))->toBe('keep');

        QrCodeService::flushPersistentCache();
        QrRenderCache::remember('k', $render);

        expect($renders)->toBe(3)
            ->and(Cache::store($store)->get('filament-qr-code:render-version'))->toBe(3)
            ->and(Cache::store($store)->get('filament-qr-code:render:v2:k'))->toBe('render-2')
            ->and(Cache::store($store)->get('app-setting'))->toBe('keep');
    } finally {
        $cleanup();
    }
})->with(['file', 'database']);

it('clears the in-process cache when an Octane operation starts', function (string $event) {
    config()->set('qr-code.generator.cache_ttl', false);
    $renders = 0;
    $render = function () use (&$renders): string {
        $renders++;

        return 'v';
    };

    QrRenderCache::remember('k', $render);
    event($event);
    QrRenderCache::remember('k', $render);

    expect($renders)->toBe(2);
})->with([
    'request' => 'Laravel\\Octane\\Events\\RequestReceived',
    'task' => 'Laravel\\Octane\\Events\\TaskReceived',
    'tick' => 'Laravel\\Octane\\Events\\TickReceived',
]);

/** @return array{string, Closure(): void} */
function isolatedRenderStore(string $driver): array
{
    if ($driver === 'file') {
        $directory = sys_get_temp_dir().'/filament-qr-code-cache-'.uniqid('', true);
        config()->set('cache.stores.qrfile', [
            'driver' => 'file',
            'path' => $directory,
        ]);
        QrCodeService::cacheStore('qrfile');

        return ['qrfile', static function () use ($directory): void {
            File::deleteDirectory($directory);
        }];
    }

    $database = sys_get_temp_dir().'/filament-qr-code-cache-'.uniqid('', true).'.sqlite';
    touch($database);
    config()->set('database.connections.qr_cache', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
    ]);
    Schema::connection('qr_cache')->create('cache', function (Blueprint $table): void {
        $table->string('key')->primary();
        $table->mediumText('value');
        $table->bigInteger('expiration')->index();
    });
    config()->set('cache.stores.qrdatabase', [
        'driver' => 'database',
        'connection' => 'qr_cache',
        'table' => 'cache',
        'lock_connection' => 'qr_cache',
        'lock_table' => 'cache_locks',
    ]);
    QrCodeService::cacheStore('qrdatabase');

    return ['qrdatabase', static function () use ($database): void {
        if (is_file($database)) {
            unlink($database);
        }
    }];
}
