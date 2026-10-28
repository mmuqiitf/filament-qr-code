<?php

declare(strict_types=1);

use Mmuqiitf\FilamentQrCode\Support\LogoPathGuard;

function logoFixture(string $path = ''): string
{
    return __DIR__.'/../Fixtures/logos'.($path === '' ? '' : '/'.$path);
}

it('allows every logo path when no base paths are configured', function () {
    config()->set('qr-code.generator.logo_base_paths', null);

    expect(LogoPathGuard::configured())->toBeFalse()
        ->and(LogoPathGuard::allows(logoFixture('outside/logo.png')))->toBeTrue()
        ->and(LogoPathGuard::allows('/does/not/exist.png'))->toBeTrue();
});

it('allows an image inside a configured base path', function () {
    config()->set('qr-code.generator.logo_base_paths', [logoFixture('inside')]);

    expect(LogoPathGuard::configured())->toBeTrue()
        ->and(LogoPathGuard::allows(logoFixture('inside/logo.png')))->toBeTrue();
});

it('rejects logos outside the base paths', function (string $path) {
    config()->set('qr-code.generator.logo_base_paths', [logoFixture('inside')]);

    expect(LogoPathGuard::allows($path))->toBeFalse();
})->with([
    'outside file' => fn () => logoFixture('outside/logo.png'),
    'dot-dot traversal' => fn () => logoFixture('inside/../outside/logo.png'),
    'missing file' => fn () => logoFixture('inside/missing.png'),
    'non-image file' => fn () => logoFixture('inside/notes.txt'),
    'sibling prefix' => fn () => logoFixture('inside-evil/logo.png'),
    'empty path' => '',
]);

it('rejects a symlink inside a base path that escapes it', function () {
    $link = sys_get_temp_dir().'/qr-guard-'.uniqid();
    mkdir($link);
    symlink(realpath(logoFixture('outside/logo.png')), $link.'/escape.png');
    copy(logoFixture('inside/logo.png'), $link.'/real.png');
    config()->set('qr-code.generator.logo_base_paths', [$link]);

    try {
        expect(LogoPathGuard::allows($link.'/escape.png'))->toBeFalse()
            ->and(LogoPathGuard::allows($link.'/real.png'))->toBeTrue();
    } finally {
        @unlink($link.'/escape.png');
        @unlink($link.'/real.png');
        @rmdir($link);
    }
});
